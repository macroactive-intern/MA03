<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCoach
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'coach') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
