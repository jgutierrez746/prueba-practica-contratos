<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario 1
        User::create([
            'name' => 'Alex',
            'email' => 'alex@bolsa.cl',
            'password' => Hash::make('password123'),
        ]);

        // Usuario 2
        User::create([
            'name' => 'Fer',
            'email' => 'fer@bolsa.cl',
            'password' => Hash::make('password123'),
        ]);

        // Usuario 3
        User::create([
            'name' => 'Sere',
            'email' => 'sere@bolsa.cl',
            'password' => Hash::make('password123'),
        ]);
    }
}