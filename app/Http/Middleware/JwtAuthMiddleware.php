<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized: Token not provided',
            ], 401);
        }

        $result = $this->jwtService->validateToken($token);

        if (! $result['valid']) {
            $isForbidden = in_array($result['error'], [
                'User account is blocked',
                'User is not an administrator',
            ], true);

            $statusCode = $isForbidden ? 403 : 401;
            $prefix = $isForbidden ? 'Forbidden: ' : 'Unauthorized: ';

            return response()->json([
                'status' => $statusCode,
                'message' => $prefix.$result['error'],
            ], $statusCode);
        }

        $request->attributes->set('jwt_payload', $result['payload']);
        $request->attributes->set('auth_user', $result['user']);

        if ($result['user']) {
            $request->setUserResolver(fn () => $result['user']);
        }

        return $next($request);
    }
}
