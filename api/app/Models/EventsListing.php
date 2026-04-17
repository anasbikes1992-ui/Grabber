<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventsListing extends Model
{
    use SoftDeletes;

    protected $table = 'events_listings';

    protected $fillable = [
        'organiser_id',
        'title',
        'description',
        'category',
        'venue_name',
        'city',
        'lat',
        'lng',
        'starts_at',
        'ends_at',
        'event_type',
        'stream_url',
        'qr_scanner_enabled',
        'total_tickets',
        'sold_tickets',
        'images',
        'status',
        'is_recurring',
        'recurring_pattern',
    ];

    protected $casts = [
        'images'              => 'array',
        'starts_at'           => 'datetime',
        'ends_at'             => 'datetime',
        'qr_scanner_enabled'  => 'boolean',
        'is_recurring'        => 'boolean',
    ];

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class, 'event_id');
    }
}
