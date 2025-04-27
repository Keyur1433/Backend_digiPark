<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all parking locations
        $parkingLocations = ParkingLocation::all();

        foreach ($parkingLocations as $parkingLocation) {
            // Create two-wheeler slots
            for ($i = 1; $i <= $parkingLocation->two_wheeler_capacity; $i++) {
                ParkingSlot::create([
                    'parking_location_id' => $parkingLocation->id,
                    'slot_number' => '2W-' . sprintf('%03d', $i),
                    'vehicle_type' => '2-wheeler',
                    'is_active' => DB::raw('true'),
                ]);
            }

            // Create four-wheeler slots
            for ($i = 1; $i <= $parkingLocation->four_wheeler_capacity; $i++) {
                ParkingSlot::create([
                    'parking_location_id' => $parkingLocation->id,
                    'slot_number' => '4W-' . sprintf('%03d', $i),
                    'vehicle_type' => '4-wheeler',
                    'is_active' => DB::raw('true'),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all parking slots
        DB::table('parking_slots')->truncate();
    }
}; 