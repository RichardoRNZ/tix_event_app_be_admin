<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function getAllVenue(Request $request){
        $perPage = $request->input('per_page', 10); 
        $search = $request->input('search');
        
        $query = Venue::select('uuid', 'name', 'desc', 'location', 'address'); 
        
        if (!empty($search)) { 
            $query->where('name', 'like', '%' . $search . '%'); 
        } 
        
        
        $data = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            "status" => 200,
            "data" => $data 
        ], 200);
    }

    public function addVenue(Request $request){
        $request->validate([
            'name' => 'required|max:255',
            'desc' => 'required|max:255',
            'location' => 'required|max:255',
            'address' => 'required|max:255',
        ]);

        try {
            $venue = new Venue();
            $venue->name = $request->name;
            $venue->desc = $request->desc;
            $venue->location = $request->location;
            $venue->address = $request->address;
            $venue->save();

            return response()->json([
                "status" => 201,
                "message" => "Venue added successfully",
                "data" => $venue
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Failed to add venue",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function editVenue(Request $request){
        if(!isset($request->uuid)){
            return response()->json([
                "status" => 400,
                "message" => "Venue ID is required",
                "data" => null
            ], 400);
        }
        
        $request->validate([
            'name' => 'required|max:255',
            'desc' => 'required|max:255',
            'location' => 'required|max:255',
            'address' => 'required|max:255',
        ]);


        try {
            $venue = Venue::where('uuid', $request->uuid)->first();
            if (!$venue) {
                return response()->json([
                    "status" => 404,
                    "message" => "Venue not found",
                    "data" => null
                ], 404);
            }

            $venue->name = $request->name;
            $venue->desc = $request->desc;
            $venue->location = $request->location;
            $venue->address = $request->address;
            $venue->save();

            return response()->json([
                "status" => 200,
                "message" => "Venue updated successfully",
                "data" => $venue
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Failed to update venue",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function deleteVenue($uuid){
        try {
            $venue = Venue::where('uuid', $uuid)->first();
            if (!$venue) {
                return response()->json([
                    "status" => 404,
                    "message" => "Venue not found",
                    "data" => null
                ], 404);
            }

            $venue->delete();

            return response()->json([
                "status" => 200,
                "message" => "Venue deleted successfully",
                "data" => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Failed to delete venue",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
