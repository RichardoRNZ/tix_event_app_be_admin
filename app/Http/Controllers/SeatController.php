<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    //
    public function getEventSeatLayout(Request $request)
    {
        if (!isset($request->event_id)) {
            return response()->json([
                "status" => 400,
                "message" => "Event ID is required",
                "data" => null
            ], 400);
        }

        $event_id = $request->event_id;
        $seat = SeatDetail::where('event_id', $event_id)->with('seat')->get();
        return response()->json([
            "status" => 200,
            "message" => "Event seat layout fetched successfully",
            "data" => $seat
        ], 200);
    }

    public function generateLayoutSeat(Request $request)
    {
        if (!isset($request->eventId)) {
            return response()->json([
                "status" => 400,
                "message" => "Event ID is required",
                "data" => null
            ], 400);
        }
        $request->validate([
            'eventId' => 'required',
            'totalRow' => 'required|integer',
            'seatPerRow' => 'required|integer',
            'startFrom' => 'required',

        ]);
        $event = Event::select('id', 'name', 'code', 'max_capacity')->where('uuid', $request->eventId)->first();
        if (!$event) {
            return response()->json([
                "status" => 404,
                "message" => "Event not found",
                "data" => null
            ], 404);
        }

        $eventMaxCapacity = $event->max_capacity;
        $totalRow = $request->totalRow;
        $seatPerRow = $request->seatPerRow;
        $startFrom = $request->startFrom;

        $totalSeat = $totalRow * $seatPerRow;
        if ($totalSeat > $eventMaxCapacity) {
            return response()->json([
                "status" => 400,
                "message" => "Total seat exceeds event max capacity",
                "data" => null
            ], 400);
        }
        DB::beginTransaction();
        try {
            $seatCount = Seat::count();
            if ($seatCount < $eventMaxCapacity) {
                $newSeats = [];
                $now = now();
                for ($i = $seatCount; $i < $eventMaxCapacity; $i++) {
                    $newSeats[] = [
                        'uuid' => (string) Str::uuid(),
                        'no' => (string) ($i + 1),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($newSeats, 1000) as $chunk) {
                    Seat::insert($chunk);
                }
            }
            
            SeatDetail::where('event_id', $event->id)->delete();

            $seatIds = Seat::orderBy('no')->limit($totalSeat)->pluck('id')->toArray();

            $currentRow = $startFrom;
            $newSeatDetails = [];
            $now = now();
            $seatIndex = 0;

            for ($i = 0; $i < $totalRow; $i++) {
                for ($j = 0; $j < $seatPerRow; $j++) {
                    $currentCol = $j + 1;
                    $newSeatDetails[] = [
                        'uuid' => (string) Str::uuid(),
                        'event_id' => $event->id,
                        'seat_id' => $seatIds[$seatIndex] ?? null,
                        'position' => "($currentRow, $currentCol)",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $seatIndex++;
                }
                $currentRow++;
            }
            
            foreach (array_chunk($newSeatDetails, 1000) as $chunk) {
                SeatDetail::insert($chunk);
            }
            
            $seatDetails = SeatDetail::where('event_id', $event->id)->get();

            DB::commit();

            return response()->json([
                "status" => 200,
                "message" => "Event seat layout generated successfully",
                "data" => $seatDetails
            ], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                "status" => 500,
                "message" => "Failed to generate event seat layout" . $th->getMessage(),
                "data" => null
            ], 500);
        }
    }
}
