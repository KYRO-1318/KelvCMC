<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'number', 'user_id', 'ticket_category_id', 'ticket_department_id', 'service_id',
        'subject', 'priority', 'status', 'last_reply_at', 'closed_at', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class, 'ticket_department_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public static function generateNumber(): string
    {
        return 'TKT-'.now()->format('Ymd').'-'.str_pad((string) (self::count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
