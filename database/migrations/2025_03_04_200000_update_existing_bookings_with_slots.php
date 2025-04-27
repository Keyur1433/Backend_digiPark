<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ParkingBooking;
use App\Models\ParkingSlot;
use App\Models\Vehicle;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bookings = ParkingBooking::whereNull('parking_slot_id')->get();
        
        foreach ($bookings as $booking) {
            // Get vehicle type for this booking
            $vehicle = Vehicle::find($booking->vehicle_id);
            $vehicleType = $vehicle ? ($vehicle->type === '2-wheeler' ? '2-wheeler' : '4-wheeler') : null;
            
            if (!$vehicleType) {
                continue; // Skip if we can't determine vehicle type
            }
            
            // Find an available slot of the same vehicle type at the parking location
            $slot = ParkingSlot::where('parking_location_id', $booking->parking_location_id)
                ->where('vehicle_type', $vehicleType)
                ->where('is_active', true)
                ->whereDoesntHave('bookings', function ($query) use ($booking) {
                    // Check for overlapping bookings
                    $query->where('status', '!=', 'cancelled')
                        ->where(function ($q) use ($booking) {
                            $q->where(function ($inner) use ($booking) {
                                // Existing booking that starts during our booking
                                $inner->where('check_in_time', '<=', $booking->check_in_time)
                                    ->where('check_out_time', '>=', $booking->check_in_time);
                            })->orWhere(function ($inner) use ($booking) {
                                // Existing booking that ends during our booking
                                $inner->where('check_in_time', '<=', $booking->check_out_time)
                                    ->where('check_out_time', '>=', $booking->check_out_time);
                            })->orWhere(function ($inner) use ($booking) {
                                // Existing booking completely within our booking
                                $inner->where('check_in_time', '>=', $booking->check_in_time)
                                    ->where('check_out_time', '<=', $booking->check_out_time);
                            });
                        });
                })
                ->first();
            
            if ($slot) {
                // If a slot is available, assign it to the booking
                $booking->parking_slot_id = $slot->id;
                $booking->save();
            } else {
                // If no slot is available, create a new one for this booking
                $slotCount = ParkingSlot::where('parking_location_id', $booking->parking_location_id)
                    ->where('vehicle_type', $vehicleType)
                    ->count();
                
                $newSlotNumber = ($vehicleType === '2-wheeler' ? '2W-' : '4W-') . sprintf('%03d', $slotCount + 1);
                
                $newSlot = ParkingSlot::create([
                    'parking_location_id' => $booking->parking_location_id,
                    'slot_number' => $newSlotNumber,
                    'vehicle_type' => $vehicleType,
                    'is_active' => true,
                ]);
                
                $booking->parking_slot_id = $newSlot->id;
                $booking->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all parking_slot_id to null in bookings
        DB::table('parking_bookings')
            ->update(['parking_slot_id' => null]);
    }
}; 