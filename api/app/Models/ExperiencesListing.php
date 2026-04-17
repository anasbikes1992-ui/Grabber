<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExperiencesListing extends Model
{
    use SoftDeletes;

    protected $table = 'experiences_listings';

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'category',
        'city',
        'lat',
        'lng',
        'price_per_person',
        'price_per_group',
        'child_price',
        'min_group',
        'max_group',
        'duration_hours',
        'weather_dependent',
        'wheelchair_accessible',
        'min_age',
        'availability_months',
        'images',
        'status',
        'rating_avg',
        'review_count',
    ];

    protected $casts = [
        'availability_months'  => 'array',
        'images'               => 'array',
        'price_per_person'     => 'float',
        'price_per_group'      => 'float',
        'child_price'          => 'float',
        'weather_dependent'    => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'rating_avg'           => 'float',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
