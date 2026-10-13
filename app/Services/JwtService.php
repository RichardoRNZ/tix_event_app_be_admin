<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JwtService
{
    
    public function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }


    public function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

   
    public function getSecretKey(): string
    {
        $secret = (string) config('jwt.secret', '');
        $decoded = base64_decode($secret, true);

        if ($decoded !== false && base64_encode($decoded) === $secret) {
            return $decoded;
        }

        return $secret;
    }

   
    public function generateToken(string $subject, int $expirationSeconds = 3600, array $customClaims = []): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $payload = array_merge([
            'sub' => $subject,
            'iat' => $now,
            'exp' => $now + $expirationSeconds,
        ], $customClaims);

        $encodedHeader = $this->base64UrlEncode((string) json_encode($header));
        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload));

        $signature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $this->getSecretKey(), true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return "{$encodedHeader}.{$encodedPayload}.{$encodedSignature}";
    }

   
    public function extractClaims(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    
    public function validateToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return [
                'valid' => false,
                'error' => 'Malformed JWT token format',
                'payload' => null,
                'user' => null,
            ];
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
            return [
                'valid' => false,
                'error' => 'Unsupported token algorithm',
                'payload' => null,
                'user' => null,
            ];
        }

        $calculatedSignature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $this->getSecretKey(), true);
        $expectedSignature = $this->base64UrlEncode($calculatedSignature);

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            return [
                'valid' => false,
                'error' => 'Invalid token signature',
                'payload' => null,
                'user' => null,
            ];
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (! is_array($payload)) {
            return [
                'valid' => false,
                'error' => 'Invalid token payload',
                'payload' => null,
                'user' => null,
            ];
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return [
                'valid' => false,
                'error' => 'Token has expired',
                'payload' => $payload,
                'user' => null,
            ];
        }

        if (config('jwt.remote.enabled')) {
            return $this->validateRemote($token, $payload);
        }

        $user = null;
        if (config('jwt.check_db') && isset($payload['sub'])) {
            try {
                $user = DB::table('users')->where('email', $payload['sub'])->first();
                if ($user) {
                    if (! empty($user->blocked)) {
                        return [
                            'valid' => false,
                            'error' => 'User account is blocked',
                            'payload' => $payload,
                            'user' => $user,
                        ];
                    }

                    if (config('jwt.require_admin') && empty($user->is_admin)) {
                        return [
                            'valid' => false,
                            'error' => 'User is not an administrator',
                            'payload' => $payload,
                            'user' => $user,
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::warning('Unable to query users table for JWT validation: '.$e->getMessage());
            }
        }

        if (config('jwt.check_tokens_table')) {
            try {
                $exists = DB::table('user_tokens')->where('token', $token)->exists();
                if (! $exists) {
                    return [
                        'valid' => false,
                        'error' => 'Token not found in active sessions',
                        'payload' => $payload,
                        'user' => $user,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Unable to query user_tokens table for JWT validation: '.$e->getMessage());
            }
        }

        return [
            'valid' => true,
            'error' => null,
            'payload' => $payload,
            'user' => $user,
        ];
    }

    /**
     * Validate token against remote Spring Boot authentication service.
     *
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, error: ?string, payload: ?array<string, mixed>, user: ?object}
     */
    protected function validateRemote(string $token, array $payload): array
    {
        $baseUrl = (string) config('jwt.remote.url', 'http://localhost:8081');
        $endpoint = (string) config('jwt.remote.profile_endpoint', '/api/auth/profile');
        $timeout = (int) config('jwt.remote.timeout', 3);

        try {
            $response = Http::timeout($timeout)
                ->withToken($token)
                ->get(rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/'));

            if ($response->successful()) {
                $userData = (object) $response->json();

                return [
                    'valid' => true,
                    'error' => null,
                    'payload' => $payload,
                    'user' => $userData,
                ];
            }

            return [
                'valid' => false,
                'error' => 'Remote Spring Boot authentication failed: '.$response->status(),
                'payload' => $payload,
                'user' => null,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Cannot connect to Spring Boot authentication service: '.$e->getMessage(),
                'payload' => $payload,
                'user' => null,
            ];
        }
    }
}
