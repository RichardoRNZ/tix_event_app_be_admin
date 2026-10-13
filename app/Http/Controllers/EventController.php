<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function getAllEvent(Request $request){
        $perPage = $request->input('per_page', 10); 
        $search = $request->input('search');
        
        $query = Event::select('uuid', 'name', 'desc', 'status', 'event_date', 'max_capacity'); 
        
        if (!empty($search)) { 
            $query->where('name', 'like', '%' . $search . '%'); 
        } 
        
        
        $data = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            "status" => 200,
            "data" => $data 
        ], 200);
    }

    public function addEvent(Request $request){
        $request->validate([
            'venue_id'     => 'required|exists:venues,id',
            'name'         => 'required|max:255',
            'desc'         => 'nullable|max:255',
            'event_date'   => 'required|date',
            'status'       => 'required|max:50',
            'max_capacity' => 'required|integer|min:1',
        ]);

        try {
            $event = new Event();
            $event->venue_id     = $request->venue_id;
            $event->name         = $request->name;
            $event->desc         = $request->desc;
            $event->event_date   = $request->event_date;
            $event->status       = $request->status;
            $event->max_capacity = $request->max_capacity;
            $event->code         = $request->code ?? null;
            $event->save();

            return response()->json([
                "status"  => 201,
                "message" => "Event added successfully",
                "data"    => $event
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"  => 500,
                "message" => "Failed to add event",
                "error"   => $e->getMessage()
            ], 500);
        }
    }

    public function editEvent(Request $request){
        if(!isset($request->uuid)){
            return response()->json([
                "status" => 400,
                "message" => "Event ID is required",
                "data" => null
            ], 400);
        }
        
        $request->validate([
            'name'         => 'required|max:255',
            'desc'         => 'nullable|max:255',
        ]);


        try {
            $event = Event::where('uuid', $request->uuid)->first();
            if (!$event) {
                return response()->json([
                    "status"  => 404,
                    "message" => "Event not found",
                    "data"    => null
                ], 404);
            }

            $event->name = $request->name;
            $event->desc = $request->desc;
            $event->save();

            return response()->json([
                "status" => 200,
                "message" => "Event updated successfully",
                "data" => $event
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Failed to update event",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function deleteEvent($uuid){
        try {
            $event = Event::where('uuid', $uuid)->first();
            if (!$event) {
                return response()->json([
                    "status" => 404,
                    "message" => "Event not found",
                    "data" => null
                ], 404);
            }

            $event->delete();

            return response()->json([
                "status" => 200,
                "message" => "Event deleted successfully",
                "data" => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Failed to delete event",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
