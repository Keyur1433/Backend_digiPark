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
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained()->onDelete('cascade');
            $table->string('slot_number');
            $table->enum('vehicle_type', ['2-wheeler', '4-wheeler']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure slot numbers are unique within a parking location
            $table->unique(['parking_location_id', 'slot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_slots');
    }
}; 