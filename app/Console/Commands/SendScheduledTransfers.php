<?php

namespace App\Console\Commands;

use App\Mail\TransferShared;
use App\Models\Transfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendScheduledTransfers extends Command
{
    protected $signature = 'transfers:send-scheduled';

    protected $description = 'Send transfer emails whose scheduled time has arrived';

    public function handle(): int
    {
        Transfer::query()
            ->whereNull('sent_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->each(function (Transfer $transfer): void {
                try {
                    Mail::to($transfer->recipient_emails)->send(new TransferShared($transfer));
                    $transfer->update(['sent_at' => now()]);
                    $this->info("Sent scheduled transfer #{$transfer->id}");
                } catch (Throwable $exception) {
                    Log::error('Scheduled transfer email failed', ['transfer_id' => $transfer->id, 'error' => $exception->getMessage()]);
                    $this->error("Failed scheduled transfer #{$transfer->id}");
                }
            });

        return self::SUCCESS;
    }
}
