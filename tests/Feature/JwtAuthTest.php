<?php

use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns 401 when accessing venues endpoint without token', function () {
    $response = $this->flushHeaders()->getJson('/api/venues');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Token not provided',
        ]);
});

it('returns 401 when accessing events endpoint without token', function () {
    $response = $this->flushHeaders()->getJson('/api/events');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Token not provided',
        ]);
});

it('returns 401 when accessing seats layout endpoint without token', function () {
    $response = $this->flushHeaders()->getJson('/api/seats/layout');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Token not provided',
        ]);
});

it('returns 401 when token is malformed', function () {
    $response = $this->withToken('invalid-non-jwt-token')->getJson('/api/venues');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Malformed JWT token format',
        ]);
});

it('returns 401 when token signature is invalid', function () {
    $jwtService = app(JwtService::class);

    // Create a token signed with an invalid/tampered key
    $header = $jwtService->base64UrlEncode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = $jwtService->base64UrlEncode((string) json_encode([
        'sub' => 'user@example.com',
        'exp' => time() + 3600,
    ]));
    $fakeSignature = $jwtService->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", 'wrong-secret-key', true));
    $tamperedToken = "{$header}.{$payload}.{$fakeSignature}";

    $response = $this->withToken($tamperedToken)->getJson('/api/venues');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Invalid token signature',
        ]);
});

it('returns 401 when token is expired', function () {
    $jwtService = app(JwtService::class);
    $expiredToken = $jwtService->generateToken('expired@example.com', -60);

    $response = $this->withToken($expiredToken)->getJson('/api/venues');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Token has expired',
        ]);
});

it('allows access with valid JWT token', function () {
    $jwtService = app(JwtService::class);
    $validToken = $jwtService->generateToken('admin@example.com', 3600);

    $response = $this->withToken($validToken)->getJson('/api/venues');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 200,
        ]);
});

it('returns 403 when user is blocked in database', function () {
    DB::table('users')->insert([
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'secret',
        'blocked' => true,
    ]);

    $jwtService = app(JwtService::class);
    $token = $jwtService->generateToken('blocked@example.com', 3600);

    $response = $this->withToken($token)->getJson('/api/venues');

    $response->assertStatus(403)
        ->assertJson([
            'status' => 403,
            'message' => 'Forbidden: User account is blocked',
        ]);
});

it('rejects token when check_tokens_table is enabled and token is missing in user_tokens', function () {
    config(['jwt.check_tokens_table' => true]);

    $jwtService = app(JwtService::class);
    $token = $jwtService->generateToken('active@example.com', 3600);

    $response = $this->withToken($token)->getJson('/api/venues');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 401,
            'message' => 'Unauthorized: Token not found in active sessions',
        ]);
});

it('accepts token when check_tokens_table is enabled and token exists in user_tokens', function () {
    config(['jwt.check_tokens_table' => true]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Active User',
        'email' => 'active@example.com',
        'password' => 'secret',
        'blocked' => false,
    ]);

    $jwtService = app(JwtService::class);
    $token = $jwtService->generateToken('active@example.com', 3600);

    DB::table('user_tokens')->insert([
        'user_id' => $userId,
        'token' => $token,
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)->getJson('/api/venues');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 200,
        ]);
});
