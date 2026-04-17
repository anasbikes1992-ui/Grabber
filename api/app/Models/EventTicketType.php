<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicketType extends Model
{
    use HasUuids;
    protected $table = 'event_ticket_types';

    protected $fillable = [
        'event_id',
        'name',
        'type',
        'price',
        'quantity',
        'sold',
        'sale_starts_at',
        'sale_ends_at',
        'is_active',
    ];

    protected $casts = [
        'price'          => 'float',
        'is_active'      => 'boolean',
        'sale_starts_at' => 'datetime',
        'sale_ends_at'   => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(EventsListing::class, 'event_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class, 'ticket_type_id');
    }
}
