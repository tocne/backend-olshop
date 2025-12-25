<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(404);
        }

        if (!auth()->user()->is_admin) {
            abort(404);
        }

        return $next($request);
    }
}
