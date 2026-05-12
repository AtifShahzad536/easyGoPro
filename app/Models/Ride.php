<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ride extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'driver_id',
        'pickup_place_name',
        'pickup_lat',
        'pickup_lng',
        'destination_place_name',
        'destination_lat',
        'destination_lng',
        'ride_type',
        'status',
        'payment_method',
        'payment_status',
        'accepted_at',
        'driver_arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'estimated_fare',
        'final_fare',
        'discount',
        'promo_code',
        'distance_km',
        'duration_minutes',
        'cancelled_by',
        'cancellation_reason',
        'rider_rating',
        'rider_review',
        'driver_rating',
        'driver_review',
        'booking_type',
        'scheduled_at',
        'linked_ride_id',
        'is_return',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'destination_lat' => 'decimal:8',
        'destination_lng' => 'decimal:8',
        'estimated_fare' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'discount' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'accepted_at' => 'datetime',
        'driver_arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_return' => 'boolean',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RideStop::class)->orderBy('stop_order');
    }

    public function linkedRide(): BelongsTo
    {
        return $this->belongsTo(Ride::class, 'linked_ride_id');
    }

    // Scope for active rides
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['searching', 'accepted', 'driver_arrived', 'ongoing']);
    }

    // Scope for rider's rides
    public function scopeByRider($query, $riderId)
    {
        return $query->where('rider_id', $riderId);
    }

    // Scope for driver's rides
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }
}
