<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Guru (Admin)
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'namalengkap' => 'Administrator',
                'password' => 'admin123',
                'role' => 'guru',
            ]
        );

        // Default Siswa
        User::firstOrCreate(
            ['username' => 'siswa'],
            [
                'namalengkap' => 'Siswa Percobaan',
                'password' => 'siswa123',
                'role' => 'siswa',
            ]
        );
    }
}
