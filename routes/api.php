<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth'])->group(function () {
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
});
