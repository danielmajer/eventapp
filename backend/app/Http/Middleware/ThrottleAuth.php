<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for authentication endpoints
 * Prevents brute force attacks on login and password reset
 */
class ThrottleAuth
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 5, int $decayMinutes = 15): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            // Log security event
            Log::warning('Rate limit exceeded for authentication', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'attempts' => $this->limiter->attempts($key),
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'message' => 'Too many attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Log failed authentication attempts
        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            Log::warning('Failed authentication attempt', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'attempts' => $this->limiter->attempts($key),
            ]);
        }

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $this->limiter->attempts($key)),
        ]);
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP + email for more granular rate limiting
        $email = $request->input('email', 'unknown');
        return sha1($request->ip() . '|' . $email . '|' . $request->path());
    }
}

