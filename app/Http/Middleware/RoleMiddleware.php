<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan user sudah login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // 2. Cek apakah role user saat ini ada di dalam array $roles yang diizinkan
        $user = Auth::user();
        if (!in_array($user->role, $roles)) {
            // Jika tidak sesuai, tolak dengan 403 Forbidden
            return response()->json([
                'message' => 'Forbidden. Akses ditolak untuk role: ' . $user->role
            ], 403);
        }

        return $next($request);
    }
}
