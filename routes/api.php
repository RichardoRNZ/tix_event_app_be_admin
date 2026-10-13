<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;



Route::prefix('venues')->group(function () {
    Route::get('/', [VenueController::class, 'getAllVenue']);
    Route::post('/', [VenueController::class, 'addVenue']);
    Route::post('/update', [VenueController::class, 'editVenue']); 
    Route::delete('/{uuid}', [VenueController::class, 'deleteVenue']);
});


Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'getAllEvent']);
    Route::post('/', [EventController::class, 'addEvent']);
    Route::post('/update', [EventController::class, 'editEvent']); 
    Route::delete('/{uuid}', [EventController::class, 'deleteEvent']);
});
Route::prefix('seats')->group(function () {
    Route::get('/layout', [SeatController::class, 'getEventSeatLayout']);
    Route::post('/generate-layout', [SeatController::class, 'generateLayoutSeat']);
});
