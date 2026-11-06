<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Mail\TransferShared;
use App\Models\Transfer;
use App\Services\TransferActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request): RedirectResponse|JsonResponse
    {
        $scheduledAt = $request->validated('scheduled_at')
            ? Carbon::parse($request->validated('scheduled_at'))
            : null;
        $availableFrom = $scheduledAt ?? now();
        $transfer = Transfer::create([
            'user_id' => $request->user()?->id,
            'token' => Str::random(32),
            'sender_email' => $request->validated('from_email'),
            'recipient_emails' => $request->validated('to_emails'),
            'message' => $request->validated('message'),
            'scheduled_at' => $scheduledAt,
            'sent_at' => $scheduledAt ? null : now(),
            'expires_at' => $availableFrom->copy()->addDays($request->integer('expires_in')),
        ]);
        $totalSize = 0;
        foreach ($request->file('files') as $file) {
            $path = $file->store('transfers/'.$transfer->token);
            $size = $file->getSize();
            $transfer->files()->create(['original_name' => $file->getClientOriginalName(), 'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $size]);
            $totalSize += $size;
        }
        $transfer->update(['file_count' => $transfer->files()->count(), 'total_size' => $totalSize]);
        if (! $scheduledAt) {
            Mail::to($transfer->recipient_emails)->send(new TransferShared($transfer));
            Log::info('Transfer email sent', [
                'sender' => $transfer->sender_email,
                'recipients' => $transfer->recipient_emails,
                'download_link' => route('transfers.show', $transfer->token),
                'expires_at' => $transfer->expires_at->toIso8601String(),
            ]);
        }

        $redirectUrl = $scheduledAt && $request->user()
            ? route('scheduled.index')
            : ($scheduledAt ? url('/') : route('transfers.show', $transfer->token));

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $scheduledAt ? 'Your transfer has been scheduled successfully.' : 'Your files were sent successfully.',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function show(Request $request, string $token, TransferActivityService $activity): View
    {
        $transfer = Transfer::where('token', $token)->with('files')->firstOrFail();
        abort_if(is_null($transfer->sent_at), 404);
        abort_if($transfer->expires_at->isPast(), 410, 'This transfer has expired.');
        $activity->record($transfer, $request, 'link_opened');

        return view('transfers.show', compact('transfer'));
    }

    public function download(Request $request, string $token, int $file, TransferActivityService $activity): StreamedResponse
    {
        $transfer = Transfer::where('token', $token)->firstOrFail();
        abort_if(is_null($transfer->sent_at), 404);
        abort_if($transfer->expires_at->isPast(), 410, 'This transfer has expired.');
        $transferFile = $transfer->files()->findOrFail($file);
        $activity->record($transfer, $request, 'file_downloaded', $transferFile);

        Transfer::query()
            ->whereKey($transfer->id)
            ->whereNull('downloaded_at')
            ->update(['downloaded_at' => now()]);

        return Storage::download($transferFile->path, $transferFile->original_name);
    }

    public function trackShare(Request $request, string $token, TransferActivityService $activity): JsonResponse
    {
        $validated = $request->validate(['channel' => ['required', 'in:copy,whatsapp']]);
        $transfer = Transfer::where('token', $token)->firstOrFail();
        abort_if(is_null($transfer->sent_at), 404);
        abort_if($transfer->expires_at->isPast(), 410, 'This transfer has expired.');

        $eventType = $validated['channel'] === 'whatsapp' ? 'whatsapp_shared' : 'link_copied';
        $activity->record($transfer, $request, $eventType, channel: $validated['channel']);

        return response()->json(['status' => 'success']);
    }
}
