<?php

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatDetail;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================
// GET /api/seats/layout - getEventSeatLayout
// ============================================================

it('returns 400 when getting seat layout without event_id', function () {
    $response = $this->getJson('/api/seats/layout');

    $response->assertStatus(400)
             ->assertJson([
                 'status'  => 400,
                 'message' => 'Event ID is required',
                 'data'    => null,
             ]);
});

it('returns seat layout for a valid event_id', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 10,
    ]);

    $seats = Seat::factory()->count(4)->create();

    foreach ($seats as $index => $seat) {
        SeatDetail::factory()->create([
            'event_id' => $event->id,
            'seat_id'  => $seat->id,
            'position' => "(" . ($index + 1) . ", 1)",
        ]);
    }

    $response = $this->getJson("/api/seats/layout?event_id={$event->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Event seat layout fetched successfully',
             ]);

    $data = $response->json('data');
    expect(count($data))->toBe(4);
});

it('returns empty seat layout for an event with no seats', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    $response = $this->getJson("/api/seats/layout?event_id={$event->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Event seat layout fetched successfully',
             ]);

    expect($response->json('data'))->toBe([]);
});

it('seat layout response includes seat relation data', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id, 'max_capacity' => 10]);
    $seat = Seat::factory()->create();
    SeatDetail::factory()->create([
        'event_id' => $event->id,
        'seat_id'  => $seat->id,
        'position' => '(1, 1)',
    ]);

    $response = $this->getJson("/api/seats/layout?event_id={$event->id}");
    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->not->toBeEmpty()
                 ->and($data[0])->toHaveKey('seat');
});

// ============================================================
// POST /api/seats/generate-layout - generateLayoutSeat
// ============================================================

it('returns 400 when generating layout without eventId', function () {
    $payload = [
        'totalRow'   => 5,
        'seatPerRow' => 10,
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);

    $response->assertStatus(400)
             ->assertJson([
                 'status'  => 400,
                 'message' => 'Event ID is required',
                 'data'    => null,
             ]);
});

it('returns 422 when generating layout with missing required fields', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id, 'max_capacity' => 100]);

    $payload = [
        'eventId' => $event->uuid,
        // missing totalRow, seatPerRow, startFrom
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['totalRow', 'seatPerRow', 'startFrom']);
});

it('returns 404 when generating layout for a non-existent event', function () {
    $payload = [
        'eventId'   => 'non-existent-uuid-0000-000000000000',
        'totalRow'   => 5,
        'seatPerRow' => 10,
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);

    $response->assertStatus(404)
             ->assertJson([
                 'status'  => 404,
                 'message' => 'Event not found',
             ]);
});

it('returns 400 when total seats exceed event max capacity', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 50,
    ]);

    $payload = [
        'eventId'    => $event->uuid,
        'totalRow'   => 10,  // 10 * 10 = 100 > 50
        'seatPerRow' => 10,
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);

    $response->assertStatus(400)
             ->assertJson([
                 'status'  => 400,
                 'message' => 'Total seat exceeds event max capacity',
             ]);
});

it('can generate seat layout successfully', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 100,
    ]);

    $payload = [
        'eventId'    => $event->uuid,
        'totalRow'   => 5,
        'seatPerRow' => 4,  // 5 * 4 = 20 seats
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Event seat layout generated successfully',
             ]);

    $data = $response->json('data');
    expect(count($data))->toBe(20);

    // Verify seat_details are created in database
    $this->assertDatabaseCount('seat_details', 20);
});

it('regenerates layout correctly when called twice (replaces old layout)', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 100,
    ]);

    // First generation
    $this->postJson('/api/seats/generate-layout', [
        'eventId'    => $event->uuid,
        'totalRow'   => 3,
        'seatPerRow' => 3,  // 9 seats
        'startFrom'  => 1,
    ]);

    // Second generation with different layout
    $response = $this->postJson('/api/seats/generate-layout', [
        'eventId'    => $event->uuid,
        'totalRow'   => 5,
        'seatPerRow' => 4,  // 20 seats
        'startFrom'  => 1,
    ]);

    $response->assertStatus(200);

    // Old seat_details should be replaced
    $seatDetailsCount = SeatDetail::where('event_id', $event->id)->count();
    expect($seatDetailsCount)->toBe(20);
});

it('generates seats with correct row positions', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 100,
    ]);

    $payload = [
        'eventId'    => $event->uuid,
        'totalRow'   => 2,
        'seatPerRow' => 3,
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);
    $response->assertStatus(200);

    // Verify positions are generated correctly
    $seatDetails = SeatDetail::where('event_id', $event->id)->get();

    // First row positions should be (1, 1), (1, 2), (1, 3)
    // Second row positions should be (2, 1), (2, 2), (2, 3)
    $positions = $seatDetails->pluck('position')->toArray();
    expect($positions)->toContain('(1, 1)')
                      ->toContain('(1, 2)')
                      ->toContain('(1, 3)')
                      ->toContain('(2, 1)')
                      ->toContain('(2, 2)')
                      ->toContain('(2, 3)');
});

it('generates new seats in pool when not enough seats exist', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id'     => $venue->id,
        'max_capacity' => 50,
    ]);

    // No seats in DB initially
    expect(Seat::count())->toBe(0);

    $payload = [
        'eventId'    => $event->uuid,
        'totalRow'   => 5,
        'seatPerRow' => 5,  // 25 seats
        'startFrom'  => 1,
    ];

    $response = $this->postJson('/api/seats/generate-layout', $payload);
    $response->assertStatus(200);

    // 50 seats should be created in pool (up to max_capacity)
    expect(Seat::count())->toBe(50);
});
