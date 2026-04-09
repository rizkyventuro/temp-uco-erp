<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah user admin sudah ada
        if (!User::where('email', 'admin@gmail.com')->exists()) {

            User::create([
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin'),
                'created_at' => now(),
                'updated_at' => now(),
                'email_verified_at' => now(),
                'is_verified_by_admin' => 2
            ]);
        }

        if (!User::where('email', 'pengepul@gmail.com')->exists()) {
            User::create([
                'name' => 'Pengepul 1',
                'email' => 'pengepul@gmail.com',
                'password' => Hash::make('admin'),
                'created_at' => now(),
                'updated_at' => now(),
                'email_verified_at' => now(),
                'is_verified_by_admin' => 2
            ]);
        }

        if (!User::where('email', 'pengepul2@gmail.com')->exists()) {
            User::create([
                'name' => 'Pengepul 2',
                'email' => 'pengepul2@gmail.com',
                'password' => Hash::make('admin'),
                'created_at' => now(),
                'updated_at' => now(),
                'email_verified_at' => now(),
                'is_verified_by_admin' => 2
            ]);
        }
    }

    public function down(): void
    {
        // Optional: hapus user jika rollback
        User::where('email', 'admin@gmail.com')->delete();
        User::where('email', 'pengepul@gmail.com')->delete();
        User::where('email', 'pengepul2@gmail.com')->delete();
    }
};
