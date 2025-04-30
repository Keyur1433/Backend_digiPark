<?php

namespace App\Console\Commands;

use App\Models\ParkingBooking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCompleteBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:auto-complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete bookings that have passed their exit time';

    /**
     * Create a new command instance.
     */
    public function __construct(private BookingService $bookingService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running auto-complete for expired bookings...');
        
        // Find all active bookings that have passed their exit time
        $expiredBookings = ParkingBooking::where('status', 'checked_in')
            ->where('check_out_time', '<', Carbon::now())
            ->get();
            
        $this->info("Found {$expiredBookings->count()} expired bookings");
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($expiredBookings as $booking) {
            try {
                DB::beginTransaction();
                
                // Complete the booking
                $success = $this->bookingService->completeBooking($booking);
                
                if ($success) {
                    $this->info("Completed booking #{$booking->id}");
                    $successCount++;
                    DB::commit();
                } else {
                    $this->error("Failed to complete booking #{$booking->id}");
                    $errorCount++;
                    DB::rollBack();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error completing booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Auto-complete booking error: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'exception' => $e
                ]);
                $errorCount++;
            }
        }
        
        $this->info("Auto-complete finished. Completed: $successCount, Errors: $errorCount");
        
        return Command::SUCCESS;
    }
} 