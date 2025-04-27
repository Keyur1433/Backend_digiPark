<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSlot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parking_location_id',
        'slot_number',
        'vehicle_type',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parking location that owns the slot.
     */
    public function parkingLocation()
    {
        return $this->belongsTo(ParkingLocation::class);
    }

    /**
     * Get the bookings for the parking slot.
     */
    public function bookings()
    {
        return $this->hasMany(ParkingBooking::class);
    }
    
    /**
     * Check if the slot is currently occupied.
     */
    public function isOccupied()
    {
        return $this->bookings()
            ->where('status', 'checked_in')
            ->exists();
    }
} 