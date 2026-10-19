<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Super Admin (Akses Penuh)
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@kandang.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin'
        ]);

        // 2. Akun Owner (Fokus Finansial & Performa)
        User::factory()->create([
            'name' => 'Bos Owner',
            'email' => 'owner@kandang.com',
            'password' => bcrypt('password'),
            'role' => 'owner'
        ]);

        // 3. Akun Anak Kandang (Fokus Eksekusi Lapangan)
        User::factory()->create([
            'name' => 'Petugas Kandang',
            'email' => 'petugas@kandang.com',
            'password' => bcrypt('password'),
            'role' => 'anak_kandang'
        ]);
    }
}