<?php

namespace App\Http\Controllers;

use App\Mail\TransferShared;
use App\Models\Transfer;
use App\Models\TransferEvent;
use App\Models\TransferFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferReportController extends Controller
{
    /** @var array<int, string> */
    private const ORDERABLE_COLUMNS = [
        0 => 'id',
        1 => 'sender_email',
        3 => 'message',
        6 => 'file_count',
        7 => 'created_at',
        8 => 'expires_at',
        9 => 'downloaded_at',
    ];

    public function index(): View
    {
        return view('reports.index');
    }

    public function data(Request $request): JsonResponse
    {
        $baseQuery = Transfer::query()
            ->with('files:id,transfer_id,original_name')
            ->withCount([
                'events as link_open_count' => fn (Builder $query): Builder => $query->where('event_type', 'link_opened'),
                'events as copy_count' => fn (Builder $query): Builder => $query->where('event_type', 'link_copied'),
                'events as whatsapp_count' => fn (Builder $query): Builder => $query->where('event_type', 'whatsapp_shared'),
                'events as download_count' => fn (Builder $query): Builder => $query->where('event_type', 'file_downloaded'),
            ])
            ->addSelect(['unique_device_count' => TransferEvent::query()
                ->selectRaw('COUNT(DISTINCT ip_hash)')
                ->whereColumn('transfer_id', 'transfers.id')
                ->whereIn('event_type', ['link_opened', 'file_downloaded'])])
            ->ownedBy($request->user());

        $recordsTotal = (clone $baseQuery)->count();
        $query = clone $baseQuery;
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('sender_email', 'like', "%{$search}%")
                    ->orWhere('recipient_emails', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('files', fn (Builder $fileQuery): Builder => $fileQuery
                        ->where('original_name', 'like', "%{$search}%"));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumn = self::ORDERABLE_COLUMNS[$request->integer('order.0.column')] ?? 'created_at';
        $orderDirection = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $start = max(0, $request->integer('start'));
        $length = min(100, max(10, $request->integer('length', 15)));

        $transfers = $query
            ->orderBy($orderColumn, $orderDirection)
            ->when($orderColumn !== 'id', fn (Builder $query): Builder => $query->orderByDesc('id'))
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $transfers->map(fn (Transfer $transfer): array => [
                'id' => $transfer->id,
                'sender_email' => $transfer->sender_email,
                'recipient_emails' => $transfer->recipient_emails,
                'message' => $transfer->message ?: '—',
                'file_names' => $transfer->files->pluck('original_name')->values()->all(),
                'files' => $transfer->files->map(fn (TransferFile $file): array => [
                    'name' => $file->original_name,
                    'view_url' => route('reports.files.view', $file),
                ])->values()->all(),
                'file_count' => $transfer->file_count,
                'total_size' => Number::fileSize($transfer->total_size),
                'sent_date' => $transfer->created_at->format('d M Y'),
                'sent_time' => $transfer->created_at->format('h:i A'),
                'expiry_date' => $transfer->expires_at->format('d M Y'),
                'expiry_time' => $transfer->expires_at->format('h:i A'),
                'download_date' => $transfer->downloaded_at?->format('d M Y'),
                'download_time' => $transfer->downloaded_at?->format('h:i A'),
                'status' => $this->status($transfer),
                'resend_url' => route('reports.resend', $transfer),
                'can_resend' => $transfer->sent_at !== null && $transfer->expires_at->isFuture(),
                'activity' => [
                    'copies' => (int) $transfer->copy_count,
                    'whatsapp' => (int) $transfer->whatsapp_count,
                    'opens' => (int) $transfer->link_open_count,
                    'downloads' => (int) $transfer->download_count,
                    'devices' => (int) $transfer->unique_device_count,
                ],
            ])->all(),
        ]);
    }

    public function viewFile(Request $request, int $file): StreamedResponse
    {
        $transferFile = TransferFile::query()
            ->whereKey($file)
            ->whereHas('transfer', fn (Builder $query): Builder => $query->ownedBy($request->user()))
            ->firstOrFail();

        abort_unless(Storage::exists($transferFile->path), 404);

        return Storage::response(
            $transferFile->path,
            $transferFile->original_name,
            ['Content-Type' => $transferFile->mime_type],
            'inline',
        );
    }

    public function resend(Request $request, int $transfer): JsonResponse
    {
        $ownedTransfer = Transfer::query()
            ->ownedBy($request->user())
            ->findOrFail($transfer);

        if (is_null($ownedTransfer->sent_at)) {
            return response()->json(['message' => 'This transfer is still scheduled and cannot be resent yet.'], 422);
        }

        if ($ownedTransfer->expires_at->isPast()) {
            return response()->json(['message' => 'This transfer has expired and cannot be resent.'], 422);
        }

        Mail::to($ownedTransfer->recipient_emails)->send(new TransferShared($ownedTransfer));

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer email sent again successfully.',
        ]);
    }

    private function status(Transfer $transfer): string
    {
        if ($transfer->downloaded_at) {
            return 'downloaded';
        }

        return $transfer->expires_at->isFuture() ? 'active' : 'expired';
    }
}
