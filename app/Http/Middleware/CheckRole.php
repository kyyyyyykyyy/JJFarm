<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Cek apakah user sudah login dan role-nya cocok dengan rute yang dituju
        if (auth()->check() && auth()->user()->role === $role) {
            return $next($request);
        }

        // Jika nekat buka halaman role lain, blokir!
        abort(403, 'Akses Ditolak: Hak akses Anda terbatas. Anda tidak diizinkan masuk ke halaman ini.');
    }
}