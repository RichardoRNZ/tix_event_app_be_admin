<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | Secret key used to sign and verify JWT tokens. By default, matches the
    | secret configured in Spring Boot service (app.jwt.secret).
    |
    */
    'secret' => env('JWT_SECRET', 'dGhpcy1pcy1hLXZlcnktc2VjdXJlLWtleS1mb3Itand0LW11c3QtYmUtYXQtbGVhc3QtMjU2LWJpdHM='),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used for HMAC signing (e.g. HS256).
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Database Verification Options
    |--------------------------------------------------------------------------
    |
    | check_db: Check whether user exists and is not blocked in the users table.
    | check_tokens_table: Check whether token exists in user_tokens table.
    | require_admin: Require that the user has is_admin = true.
    |
    */
    'check_db' => env('JWT_CHECK_DB', true),
    'check_tokens_table' => env('JWT_CHECK_USER_TOKENS', false),
    'require_admin' => env('JWT_REQUIRE_ADMIN', false),

    /*
    |--------------------------------------------------------------------------
    | Spring Boot Remote Validation
    |--------------------------------------------------------------------------
    |
    | Optionally validate tokens by calling the Spring Boot service directly.
    |
    */
    'remote' => [
        'enabled' => env('JWT_REMOTE_VALIDATION', false),
        'url' => env('SPRINGBOOT_AUTH_URL', 'http://localhost:8081'),
        'profile_endpoint' => env('SPRINGBOOT_AUTH_PROFILE_ENDPOINT', '/api/auth/profile'),
        'timeout' => (int) env('SPRINGBOOT_AUTH_TIMEOUT', 3),
    ],
];
