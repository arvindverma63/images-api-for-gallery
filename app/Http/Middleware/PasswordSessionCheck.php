<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordSessionCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('password') !== '36902003') {
            return redirect('/login');
        }

        return $next($request); // ✅ Continue to /gallery
    }
}
