<?php

namespace Tests;

use App\Services\JwtService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->boolean('is_admin')->default(false);
                $table->boolean('blocked')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_tokens')) {
            Schema::create('user_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->text('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Set a valid JWT Bearer authorization header for requests.
     */
    public function withJwtAuth(?string $email = 'admin@example.com'): static
    {
        $token = app(JwtService::class)->generateToken($email ?? 'admin@example.com');

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
