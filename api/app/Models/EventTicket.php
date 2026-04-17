<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicket extends Model
{
    use HasUuids;
    protected $table = 'event_tickets';

    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'customer_id',
        'ticket_code',
        'status',
        'price_paid',
        'purchased_at',
        'used_at',
    ];

    protected $casts = [
        'price_paid' => 'float',
        'purchased_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(EventsListing::class, 'event_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(EventTicketType::class, 'ticket_type_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(TicketScan::class, 'ticket_id');
    }
}
