<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'email' => 'admin@zinus.com',
            'password' => Hash::make('0838jangan'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);
    }
}
