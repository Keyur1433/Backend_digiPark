<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParkingSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parking_location_id' => $this->parking_location_id,
            'slot_number' => $this->slot_number,
            'vehicle_type' => $this->vehicle_type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_occupied' => $this->checkOccupied(),
            'parking_location' => $this->when($this->relationLoaded('parkingLocation'), new ParkingLocationResource($this->parkingLocation)),
        ];
    }
} 