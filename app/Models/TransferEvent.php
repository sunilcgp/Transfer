<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['transfer_id', 'transfer_file_id', 'event_type', 'channel', 'ip_hash', 'user_agent', 'referrer', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}
