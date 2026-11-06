<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferFile extends Model
{
    protected $fillable = ['transfer_id', 'original_name', 'path', 'mime_type', 'size'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}
