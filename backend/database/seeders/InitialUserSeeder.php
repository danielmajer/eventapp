<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InitialUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'dmajre25@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Cool123!'),
                'role' => 'admin',
            ]
        );
    }
}
