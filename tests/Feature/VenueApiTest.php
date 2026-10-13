<?php

use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================
// GET /api/venues - getAllVenue
// ============================================================

it('can get all venues with default pagination', function () {
    Venue::factory()->count(5)->create();

    $response = $this->getJson('/api/venues');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'status',
                 'data' => [
                     'data',
                     'current_page',
                     'per_page',
                     'total',
                 ],
             ])
             ->assertJson(['status' => 200]);
});

it('returns paginated venues with custom per_page', function () {
    Venue::factory()->count(15)->create();

    $response = $this->getJson('/api/venues?per_page=5');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    $data = $response->json('data');
    expect($data['per_page'])->toBe(5)
                             ->and($data['total'])->toBe(15);
});

it('can search venues by name', function () {
    Venue::factory()->create(['name' => 'Jakarta Convention Center']);
    Venue::factory()->create(['name' => 'Bali International Arena']);
    Venue::factory()->create(['name' => 'Surabaya Sports Hall']);

    $response = $this->getJson('/api/venues?search=jakarta');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    $results = $response->json('data.data');
    expect(count($results))->toBe(1)
                           ->and($results[0]['name'])->toBe('Jakarta Convention Center');
});

it('returns empty data when no venues exist', function () {
    $response = $this->getJson('/api/venues');

    $response->assertStatus(200)
             ->assertJson(['status' => 200]);

    expect($response->json('data.total'))->toBe(0);
});

it('returns venues ordered by name ascending', function () {
    Venue::factory()->create(['name' => 'Zebra Hall']);
    Venue::factory()->create(['name' => 'Alpha Arena']);
    Venue::factory()->create(['name' => 'Mango Convention']);

    $response = $this->getJson('/api/venues');
    $response->assertStatus(200);

    $names = collect($response->json('data.data'))->pluck('name')->toArray();
    $sorted = $names;
    sort($sorted);

    expect($names)->toBe($sorted);
});

// ============================================================
// POST /api/venues - addVenue
// ============================================================

it('can create a new venue with valid data', function () {
    $payload = [
        'name'     => 'Jakarta Convention Center',
        'desc'     => 'A large convention center in Jakarta',
        'location' => 'South Jakarta',
        'address'  => 'Jl. Gatot Subroto No.1, Jakarta',
    ];

    $response = $this->postJson('/api/venues', $payload);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'status',
                 'message',
                 'data' => ['uuid', 'name', 'desc', 'location', 'address'],
             ])
             ->assertJson([
                 'status'  => 201,
                 'message' => 'Venue added successfully',
             ]);

    $this->assertDatabaseHas('venues', ['name' => 'Jakarta Convention Center']);
});

it('auto-generates uuid when creating a venue', function () {
    $payload = [
        'name'     => 'Test Venue',
        'desc'     => 'Test Description',
        'location' => 'Test Location',
        'address'  => 'Test Address',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(201);

    $uuid = $response->json('data.uuid');
    expect($uuid)->not->toBeNull()
                 ->and($uuid)->toMatch('/^[0-9a-f-]{36}$/');
});

it('returns 422 when creating a venue without name', function () {
    $payload = [
        'desc'     => 'A large convention center',
        'location' => 'South Jakarta',
        'address'  => 'Jl. Gatot Subroto No.1',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

it('returns 422 when creating a venue without desc', function () {
    $payload = [
        'name'     => 'Jakarta Convention Center',
        'location' => 'South Jakarta',
        'address'  => 'Jl. Gatot Subroto No.1',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['desc']);
});

it('returns 422 when creating a venue without location', function () {
    $payload = [
        'name'    => 'Jakarta Convention Center',
        'desc'    => 'A large convention center',
        'address' => 'Jl. Gatot Subroto No.1',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['location']);
});

it('returns 422 when creating a venue without address', function () {
    $payload = [
        'name'     => 'Jakarta Convention Center',
        'desc'     => 'A large convention center',
        'location' => 'South Jakarta',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['address']);
});

it('returns 422 when name exceeds 255 characters', function () {
    $payload = [
        'name'     => str_repeat('A', 256),
        'desc'     => 'Description',
        'location' => 'Location',
        'address'  => 'Address',
    ];

    $response = $this->postJson('/api/venues', $payload);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

// ============================================================
// POST /api/venues/update - editVenue
// ============================================================

it('can update an existing venue', function () {
    $venue = Venue::factory()->create([
        'name'     => 'Old Name',
        'desc'     => 'Old Desc',
        'location' => 'Old Location',
        'address'  => 'Old Address',
    ]);

    $payload = [
        'uuid'     => $venue->uuid,
        'name'     => 'New Name',
        'desc'     => 'New Desc',
        'location' => 'New Location',
        'address'  => 'New Address',
    ];

    $response = $this->postJson('/api/venues/update', $payload);

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Venue updated successfully',
             ]);

    $this->assertDatabaseHas('venues', ['name' => 'New Name']);
});

it('returns 400 when updating venue without uuid', function () {
    $payload = [
        'name'     => 'New Name',
        'desc'     => 'New Desc',
        'location' => 'New Location',
        'address'  => 'New Address',
    ];

    $response = $this->postJson('/api/venues/update', $payload);

    $response->assertStatus(400)
             ->assertJson([
                 'status'  => 400,
                 'message' => 'Venue ID is required',
             ]);
});

it('returns 404 when updating a venue that does not exist', function () {
    $payload = [
        'uuid'     => 'non-existent-uuid-0000-000000000000',
        'name'     => 'New Name',
        'desc'     => 'New Desc',
        'location' => 'New Location',
        'address'  => 'New Address',
    ];

    $response = $this->postJson('/api/venues/update', $payload);

    $response->assertStatus(404)
             ->assertJson([
                 'status'  => 404,
                 'message' => 'Venue not found',
             ]);
});

it('returns 422 when updating venue with missing required fields', function () {
    $venue = Venue::factory()->create();

    $payload = [
        'uuid' => $venue->uuid,
        // missing name, desc, location, address
    ];

    $response = $this->postJson('/api/venues/update', $payload);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'desc', 'location', 'address']);
});

// ============================================================
// DELETE /api/venues/{uuid} - deleteVenue
// ============================================================

it('can delete an existing venue (soft delete)', function () {
    $venue = Venue::factory()->create();

    $response = $this->deleteJson("/api/venues/{$venue->uuid}");

    $response->assertStatus(200)
             ->assertJson([
                 'status'  => 200,
                 'message' => 'Venue deleted successfully',
                 'data'    => null,
             ]);

    $this->assertSoftDeleted('venues', ['uuid' => $venue->uuid]);
});

it('returns 404 when deleting a venue that does not exist', function () {
    $response = $this->deleteJson('/api/venues/non-existent-uuid');

    $response->assertStatus(404)
             ->assertJson([
                 'status'  => 404,
                 'message' => 'Venue not found',
             ]);
});
