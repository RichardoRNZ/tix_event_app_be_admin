<?php

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================
// GET /api/events - getAllEvent
// NOTE: Bug detected - EventController::getAllEvent selects 
// 'location' and 'address' columns that don't exist in events table.
// Tests will document both expected behaviour and actual bug.
// ============================================================

it('can get all events with default pagination', function () {
    $venue = Venue::factory()->create();
    Event::factory()->count(3)->create(['venue_id' => $venue->id]);

    $response = $this->getJson('/api/events');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'status',
                 'data',
             ])
             ->assertJson(['status' => 200]);
});

it('returns paginated events with custom per_page', function () {
    $venue = Venue::factory()->create();
    Event::factory()->count(15)->create(['venue_id' => $venue->id]);

    $response = $this->getJson('/api/events?per_page=5');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    $data = $response->json('data');
    expect($data['per_page'])->toBe(5)
                             ->and($data['total'])->toBe(15);
});

it('can search events by name', function () {
    $venue = Venue::factory()->create();
    Event::factory()->create(['venue_id' => $venue->id, 'name' => 'Jakarta Music Festival']);
    Event::factory()->create(['venue_id' => $venue->id, 'name' => 'Bali Dance Show']);
    Event::factory()->create(['venue_id' => $venue->id, 'name' => 'Surabaya Expo']);

    $response = $this->getJson('/api/events?search=jakarta');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    $results = $response->json('data.data');
    expect(count($results))->toBe(1)
                           ->and($results[0]['name'])->toBe('Jakarta Music Festival');
});

it('returns empty data when no events exist', function () {
    $response = $this->getJson('/api/events');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    expect($response->json('data.total'))->toBe(0);
});

// ============================================================
// POST /api/events - addEvent
// ============================================================

it('can create a new event with valid data', function () {
    $venue = Venue::factory()->create();

    $payload = [
        'venue_id'     => $venue->id,
        'name'         => 'Jakarta Music Festival',
        'desc'         => 'A grand music festival in Jakarta',
        'event_date'   => now()->addDays(30)->format('Y-m-d H:i:s'),
        'status'       => 'active',
        'max_capacity' => 500,
    ];

    $response = $this->postJson('/api/events', $payload);

    // Controller validates: venue_id, name, desc, event_date, status, max_capacity
    $response->assertStatus(201)
             ->assertJsonStructure([
                 'status',
                 'message',
                 'data' => ['uuid', 'name', 'desc'],
             ])
             ->assertJson([
                 'status'  => 201,
                 'message' => 'Event added successfully',
             ]);

    $this->assertDatabaseHas('events', ['name' => 'Jakarta Music Festival']);
});

it('auto-generates uuid when creating an event', function () {
    $venue = Venue::factory()->create();

    $payload = [
        'venue_id'     => $venue->id,
        'name'         => 'Test Event',
        'desc'         => 'Test Description',
        'event_date'   => now()->addDays(10)->format('Y-m-d H:i:s'),
        'status'       => 'active',
        'max_capacity' => 100,
    ];

    $response = $this->postJson('/api/events', $payload);
    $response->assertStatus(201);

    $uuid = $response->json('data.uuid');
    expect($uuid)->not->toBeNull()
                 ->and($uuid)->toMatch('/^[0-9a-f-]{36}$/');
});

it('returns 422 when creating an event without required fields', function () {
    // Sending completely empty body - should trigger multiple validation errors
    $response = $this->postJson('/api/events', []);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['venue_id', 'name', 'event_date', 'status', 'max_capacity']);
});

it('returns 422 when creating an event without venue_id', function () {
    $payload = [
        'name'         => 'Event Name',
        'event_date'   => now()->addDays(10)->format('Y-m-d H:i:s'),
        'status'       => 'active',
        'max_capacity' => 100,
    ];

    $response = $this->postJson('/api/events', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['venue_id']);
});

it('returns 422 when creating an event without name', function () {
    $venue = Venue::factory()->create();
    $payload = [
        'venue_id'     => $venue->id,
        'desc'         => 'Description',
        'event_date'   => now()->addDays(10)->format('Y-m-d H:i:s'),
        'status'       => 'active',
        'max_capacity' => 100,
    ];

    $response = $this->postJson('/api/events', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

it('returns 422 when name exceeds 255 characters', function () {
    $venue = Venue::factory()->create();
    $payload = [
        'venue_id'     => $venue->id,
        'name'         => str_repeat('A', 256),
        'event_date'   => now()->addDays(10)->format('Y-m-d H:i:s'),
        'status'       => 'active',
        'max_capacity' => 100,
    ];

    $response = $this->postJson('/api/events', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

// ============================================================
// POST /api/events/update - editEvent
// ============================================================

it('can update an existing event', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'venue_id' => $venue->id,
        'name'     => 'Old Event Name',
        'desc'     => 'Old Description',
    ]);

    $payload = [
        'uuid' => $event->uuid,
        'name' => 'New Event Name',
        'desc' => 'New Description',
    ];

    $response = $this->postJson('/api/events/update', $payload);

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Event updated successfully',
             ]);

    $this->assertDatabaseHas('events', ['name' => 'New Event Name']);
});

it('returns 400 when updating event without uuid', function () {
    $payload = [
        'name' => 'New Name',
        'desc' => 'New Desc',
    ];

    $response = $this->postJson('/api/events/update', $payload);

    $response->assertStatus(400)
             ->assertJson([
                 'status'  => 400,
                 'message' => 'Event ID is required',
             ]);
});

it('returns 404 when updating an event that does not exist', function () {
    $payload = [
        'uuid' => 'non-existent-uuid-0000-000000000000',
        'name' => 'New Name',
        'desc' => 'New Desc',
    ];

    $response = $this->postJson('/api/events/update', $payload);

    $response->assertStatus(404)
             ->assertJson([
                 'status'  => 404,
                 'message' => 'Event not found',
             ]);
});

it('returns 422 when updating event with missing required fields', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    $payload = [
        'uuid' => $event->uuid,
        // missing name, desc, location, address
    ];

    $response = $this->postJson('/api/events/update', $payload);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']); // desc is nullable in editEvent
});

// ============================================================
// DELETE /api/events/{uuid} - deleteEvent
// ============================================================

it('can delete an existing event', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    $response = $this->deleteJson("/api/events/{$event->uuid}");

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Event deleted successfully',
                 'data'    => null,
             ]);

    $this->assertSoftDeleted('events', ['uuid' => $event->uuid]);
});

it('returns 404 when deleting an event that does not exist', function () {
    $response = $this->deleteJson('/api/events/non-existent-uuid');

    $response->assertStatus(404)
             ->assertJson([
                 'status'  => 404,
                 'message' => 'Event not found',
             ]);
});
