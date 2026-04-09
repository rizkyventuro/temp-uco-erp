<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasUuids;

    const TYPE_INFO    = 1;
    const TYPE_SUCCESS = 2;
    const TYPE_WARNING = 3;
    const TYPE_ERROR   = 4;

    protected $fillable = [
        'type',
        'title',
        'message',
        'icon',
        'url',
        'notifiable_id',
        'notifiable_type',
        'sender_id',
        'read_at',
    ];

    protected $casts = [
        'type'    => 'integer',
        'read_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ==================== Scopes ====================

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType(Builder $query, int $type): Builder
    {
        return $query->where('type', $type);
    }

    // ==================== Helpers ====================

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'success',
            self::TYPE_WARNING => 'warning',
            self::TYPE_ERROR   => 'error',
            default            => 'info',
        };
    }
}
