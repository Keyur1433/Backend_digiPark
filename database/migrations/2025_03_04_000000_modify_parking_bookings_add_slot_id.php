<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_bookings', function (Blueprint $table) {
            // Add parking_slot_id column
            $table->foreignId('parking_slot_id')->nullable()->after('parking_location_id');

            // Add foreign key constraint
            $table->foreign('parking_slot_id')->references('id')->on('parking_slots')->onDelete('cascade');
        });

        // We're keeping parking_location_id for now to maintain backward compatibility
        // during the migration process. We'll remove it in a later migration once
        // all bookings have been properly associated with slots.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_bookings', function (Blueprint $table) {
            $table->dropForeign(['parking_slot_id']);
            $table->dropColumn('parking_slot_id');
        });
    }
}; 