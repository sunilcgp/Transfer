<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\TransferEvent;
use App\Models\TransferFile;
use Illuminate\Http\Request;

class TransferActivityService
{
    public function record(Transfer $transfer, Request $request, string $eventType, ?TransferFile $file = null, ?string $channel = null): TransferEvent
    {
        $ip = $request->ip();

        return $transfer->events()->create([
            'transfer_file_id' => $file?->id,
            'event_type' => $eventType,
            'channel' => $channel,
            'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
            'referrer' => $request->headers->get('referer'),
            'created_at' => now(),
        ]);
    }
}
