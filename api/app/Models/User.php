<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'phone_verified_at',
        'email_verified_at',
        'referred_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProviderWallet::class);
    }

    public function pearlPoints(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PearlPointsBalance::class);
    }

    public function staysListings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaysListing::class, 'host_id');
    }

    public function vehicleListings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleListing::class, 'owner_id');
    }

    public function bookingsAsCustomer(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function bookingsAsProvider(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function notifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
}
