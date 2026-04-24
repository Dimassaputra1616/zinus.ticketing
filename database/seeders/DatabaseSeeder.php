<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dimassputra1616@gmail.com'],
            [
                'name' => 'Dimas Saputra',
                'password' => Hash::make('0838jangan'),
                'role' => 'admin',
                'is_admin' => 1,
            ]
        );
    }
}
