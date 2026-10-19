<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/UserManagement', [
            'users' => User::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,owner,anak_kandang',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:super_admin,owner,anak_kandang',
            'reset_password' => 'nullable|boolean', // Menangkap checkbox
        ]);

        // Jika admin mencentang fitur "Reset Password"
        if ($request->reset_password) {
            $validated['password'] = Hash::make('password'); // Password dikembalikan ke default: "password"
        }

        // Hapus key reset_password dari array agar tidak bentrok dengan database
        unset($validated['reset_password']);

        $user->update($validated);
        return redirect()->back();
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->withErrors(['message' => 'Tindakan ilegal: Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        $user->delete();
        return redirect()->back();
    }
}