<?php

namespace App\Models;

use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'token', 'expires_at', 'downloaded_at', 'scheduled_at', 'sent_at', 'file_count', 'total_size', 'sender_email', 'recipient_emails', 'message'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'downloaded_at' => 'datetime', 'scheduled_at' => 'datetime', 'sent_at' => 'datetime', 'recipient_emails' => 'array'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(TransferFile::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TransferEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhere(function (Builder $legacyQuery) use ($user): void {
                    $legacyQuery->whereNull('user_id')
                        ->where('sender_email', $user->email);
                });
        });
    }
}
