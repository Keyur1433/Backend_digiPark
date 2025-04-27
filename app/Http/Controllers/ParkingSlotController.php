<?php

namespace App\Http\Controllers;

use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParkingSlotController extends Controller
{
    /**
     * Display a listing of parking slots for a specific parking location.
     */
    public function index(ParkingLocation $parkingLocation): JsonResponse
    {
        // Check if user owns the parking location or is admin
        if (Auth::user()->role !== 'admin' && $parkingLocation->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $slots = $parkingLocation->slots()
            ->orderBy('slot_number')
            ->get();

        return response()->json([
            'slots' => $slots,
        ]);
    }

    /**
     * Store a newly created parking slot in storage.
     */
    public function store(Request $request, ParkingLocation $parkingLocation): JsonResponse
    {
        // Check if user owns the parking location
        if ($parkingLocation->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Validate request
        $request->validate([
            'slot_number' => 'required|string|max:10',
            'vehicle_type' => 'required|in:2-wheeler,4-wheeler',
        ]);

        // Check if slot number already exists for this parking location
        $slotExists = $parkingLocation->slots()
            ->where('slot_number', $request->slot_number)
            ->exists();

        if ($slotExists) {
            return response()->json([
                'message' => 'Slot number already exists for this parking location.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create new slot
            $slot = $parkingLocation->slots()->create([
                'slot_number' => $request->slot_number,
                'vehicle_type' => $request->vehicle_type,
                'is_active' => DB::raw('TRUE'),
            ]);

            // Update the corresponding slot availability
            $slotAvailability = $parkingLocation->slotAvailabilities()
                ->where('vehicle_type', $request->vehicle_type)
                ->first();

            if ($slotAvailability) {
                $slotAvailability->total_slots += 1;
                $slotAvailability->available_slots += 1;
                $slotAvailability->save();
            }

            // Update parking location capacity
            if ($request->vehicle_type === '2-wheeler') {
                $parkingLocation->two_wheeler_capacity += 1;
            } else {
                $parkingLocation->four_wheeler_capacity += 1;
            }
            $parkingLocation->save();

            DB::commit();

            return response()->json([
                'message' => 'Parking slot created successfully.',
                'slot' => $slot,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create parking slot.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified parking slot.
     */
    public function show(ParkingLocation $parkingLocation, ParkingSlot $parkingSlot): JsonResponse
    {
        // Check if slot belongs to the parking location
        if ($parkingSlot->parking_location_id !== $parkingLocation->id) {
            return response()->json([
                'message' => 'Slot does not belong to the specified parking location.',
            ], 404);
        }

        // Check if user owns the parking location or is admin
        if (Auth::user()->role !== 'admin' && $parkingLocation->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'slot' => $parkingSlot,
        ]);
    }

    /**
     * Update the specified parking slot in storage.
     */
    public function update(Request $request, ParkingLocation $parkingLocation, ParkingSlot $parkingSlot): JsonResponse
    {
        // Check if slot belongs to the parking location
        if ($parkingSlot->parking_location_id !== $parkingLocation->id) {
            return response()->json([
                'message' => 'Slot does not belong to the specified parking location.',
            ], 404);
        }

        // Check if user owns the parking location
        if ($parkingLocation->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Validate request
        $request->validate([
            'slot_number' => 'required|string|max:10',
            'is_active' => 'required|boolean',
        ]);

        // Check if the new slot number already exists (if changed)
        if ($request->slot_number !== $parkingSlot->slot_number) {
            $slotExists = $parkingLocation->slots()
                ->where('slot_number', $request->slot_number)
                ->where('id', '!=', $parkingSlot->id)
                ->exists();

            if ($slotExists) {
                return response()->json([
                    'message' => 'Slot number already exists for this parking location.',
                ], 422);
            }
        }

        // Check if the slot is currently occupied and trying to deactivate
        if ($parkingSlot->is_active && !$request->is_active && $parkingSlot->isOccupied()) {
            return response()->json([
                'message' => 'Cannot deactivate an occupied parking slot.',
            ], 422);
        }

        // Update the slot
        $parkingSlot->update([
            'slot_number' => $request->slot_number,
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'message' => 'Parking slot updated successfully.',
            'slot' => $parkingSlot,
        ]);
    }

    /**
     * Remove the specified parking slot from storage.
     */
    public function destroy(ParkingLocation $parkingLocation, ParkingSlot $parkingSlot): JsonResponse
    {
        // Check if slot belongs to the parking location
        if ($parkingSlot->parking_location_id !== $parkingLocation->id) {
            return response()->json([
                'message' => 'Slot does not belong to the specified parking location.',
            ], 404);
        }

        // Check if user owns the parking location
        if ($parkingLocation->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Check if the slot is currently occupied
        if ($parkingSlot->isOccupied()) {
            return response()->json([
                'message' => 'Cannot delete an occupied parking slot.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update slot availability
            $slotAvailability = $parkingLocation->slotAvailabilities()
                ->where('vehicle_type', $parkingSlot->vehicle_type)
                ->first();

            if ($slotAvailability) {
                $slotAvailability->total_slots -= 1;
                $slotAvailability->available_slots -= 1;
                $slotAvailability->save();
            }

            // Update parking location capacity
            if ($parkingSlot->vehicle_type === '2-wheeler') {
                $parkingLocation->two_wheeler_capacity -= 1;
            } else {
                $parkingLocation->four_wheeler_capacity -= 1;
            }
            $parkingLocation->save();

            // Delete the slot
            $parkingSlot->delete();

            DB::commit();

            return response()->json([
                'message' => 'Parking slot deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete parking slot.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of available parking slots for a specific parking location.
     */
    public function getAvailableSlots(Request $request, ParkingLocation $parkingLocation): JsonResponse
    {
        // Validate request
        $request->validate([
            'vehicle_type' => 'nullable|in:2-wheeler,4-wheeler',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);

        // Check if parking location is active
        if (!$parkingLocation->is_active) {
            return response()->json([
                'message' => 'This parking location is not active.',
            ], 422);
        }

        $date = $request->date;
        $vehicleType = $request->vehicle_type;
        $startTime = $request->start_time;
        $endTime = $request->end_time;

        // Get all slots with optional vehicle type filter
        $query = $parkingLocation->slots()
            ->where('is_active', DB::raw('TRUE'))
            ->with(['bookings' => function($query) use ($date, $startTime, $endTime) {
                $query->whereDate('check_in_time', $date)
                    ->where('status', '!=', 'cancelled');

                // If time range is provided, filter bookings within that range
                if ($startTime && $endTime) {
                    $query->where(function($q) use ($startTime, $endTime) {
                        $q->where(function($inner) use ($startTime, $endTime) {
                            // Booking starts during the requested time period
                            $inner->whereTime('check_in_time', '>=', $startTime)
                                ->whereTime('check_in_time', '<', $endTime);
                        })->orWhere(function($inner) use ($startTime, $endTime) {
                            // Booking ends during the requested time period
                            $inner->whereTime('check_out_time', '>', $startTime)
                                ->whereTime('check_out_time', '<=', $endTime);
                        })->orWhere(function($inner) use ($startTime, $endTime) {
                            // Booking completely covers the requested time period
                            $inner->whereTime('check_in_time', '<=', $startTime)
                                ->whereTime('check_out_time', '>=', $endTime);
                        });
                    });
                }
            }]);

        if ($vehicleType) {
            $query->where('vehicle_type', $vehicleType);
        }

        $slots = $query->get();

        // Transform slots with booking information
        $transformedSlots = $slots->map(function ($slot) use ($startTime, $endTime) {
            $isOccupied = $slot->checkOccupied();
            $hasUpcomingBooking = $slot->bookings->isNotEmpty();
            
            // If time range is provided, check if slot is available for that specific period
            $isAvailableForTimeRange = true;
            if ($startTime && $endTime) {
                $isAvailableForTimeRange = !$slot->bookings->contains(function ($booking) use ($startTime, $endTime) {
                    return $booking->status !== 'cancelled' &&
                        (
                            // Booking starts during the requested time period
                            (strtotime($booking->check_in_time->format('H:i')) >= strtotime($startTime) && 
                             strtotime($booking->check_in_time->format('H:i')) < strtotime($endTime)) ||
                            // Booking ends during the requested time period
                            (strtotime($booking->check_out_time->format('H:i')) > strtotime($startTime) && 
                             strtotime($booking->check_out_time->format('H:i')) <= strtotime($endTime)) ||
                            // Booking completely covers the requested time period
                            (strtotime($booking->check_in_time->format('H:i')) <= strtotime($startTime) && 
                             strtotime($booking->check_out_time->format('H:i')) >= strtotime($endTime))
                        );
                });
            }
            
            return [
                'id' => $slot->id,
                'slot_number' => $slot->slot_number,
                'vehicle_type' => $slot->vehicle_type,
                'is_active' => $slot->is_active,
                'is_occupied' => $isOccupied,
                'has_upcoming_booking' => $hasUpcomingBooking,
                'is_available_for_time_range' => $isAvailableForTimeRange,
                'bookings' => $slot->bookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'check_in_time' => $booking->check_in_time,
                        'check_out_time' => $booking->check_out_time,
                        'status' => $booking->status,
                    ];
                }),
                'status' => $isOccupied ? 'occupied' : 
                           (!$isAvailableForTimeRange ? 'booked' : 
                           ($hasUpcomingBooking ? 'partially_available' : 'available')),
            ];
        });

        // Get slot availability information for both vehicle types if no specific type is requested
        $slotAvailabilities = [];
        if (!$vehicleType) {
            $slotAvailabilities = $parkingLocation->slotAvailabilities()
                ->get()
                ->map(function ($availability) {
                    return [
                        'vehicle_type' => $availability->vehicle_type,
                        'total_slots' => $availability->total_slots,
                        'available_slots' => $availability->available_slots,
                    ];
                });
        } else {
            $slotAvailability = $parkingLocation->slotAvailabilities()
                ->where('vehicle_type', $vehicleType)
                ->first();
            
            if ($slotAvailability) {
                $slotAvailabilities = [[
                    'vehicle_type' => $slotAvailability->vehicle_type,
                    'total_slots' => $slotAvailability->total_slots,
                    'available_slots' => $slotAvailability->available_slots,
                ]];
            }
        }

        return response()->json([
            'slots' => $transformedSlots,
            'total_slots' => $slots->count(),
            'available_slots' => $slots->where('isOccupied', false)->count(),
            'slot_availabilities' => $slotAvailabilities,
            'date' => $date,
            'vehicle_type' => $vehicleType,
            'time_range' => $startTime && $endTime ? [
                'start_time' => $startTime,
                'end_time' => $endTime
            ] : null,
        ]);
    }
}
