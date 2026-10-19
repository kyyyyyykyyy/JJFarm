<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SecurityLog; // IMPORT MODEL SECURITY LOG
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // REKAM JEJAK LOGIN
        SecurityLog::create([
            'actor' => auth()->user()->name,
            'role' => auth()->user()->role,
            'action' => 'LOGIN',
            'desc' => 'Otentikasi berhasil dari IP: ' . $request->ip(),
            'status' => 'Success'
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        // REKAM JEJAK LOGOUT SEBELUM SESI DIHAPUS
        if ($user) {
            SecurityLog::create([
                'actor' => $user->name,
                'role' => $user->role,
                'action' => 'LOGOUT',
                'desc' => 'Sesi diakhiri dengan aman dari IP: ' . $request->ip(),
                'status' => 'Success'
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}