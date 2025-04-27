<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration should only be executed after all data has been migrated
     * to use the parking_slot_id relationship.
     */
    public function up(): void
    {
        // We're only commenting this out for now, to be uncommented after data migration
        /*
        Schema::table('parking_bookings', function (Blueprint $table) {
            $table->dropForeign(['parking_location_id']);
            $table->dropColumn('parking_location_id');
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We're only commenting this out for now, to match the up method
        /*
        Schema::table('parking_bookings', function (Blueprint $table) {
            $table->foreignId('parking_location_id')->after('vehicle_id');
            $table->foreign('parking_location_id')->references('id')->on('parking_locations')->onDelete('cascade');
        });
        */
    }
}; 