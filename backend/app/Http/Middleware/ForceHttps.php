<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce HTTPS/TLS encryption.
 * 
 * This middleware ensures that all API requests are made over HTTPS.
 * Unencrypted HTTP requests will be rejected in production.
 */
class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // In production, enforce HTTPS
        // Check both direct HTTPS and X-Forwarded-Proto header (for proxies/load balancers)
        $isSecure = $request->secure() || 
                    $request->header('X-Forwarded-Proto') === 'https' ||
                    $request->server('HTTP_X_FORWARDED_PROTO') === 'https';
        
        if (app()->environment('production') && !$isSecure) {
            return response()->json([
                'message' => 'HTTPS is required. Unencrypted communication is prohibited.',
                'error' => 'TLS_REQUIRED'
            ], 403);
        }

        // Set security headers
        $response = $next($request);

        // Security headers
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}

