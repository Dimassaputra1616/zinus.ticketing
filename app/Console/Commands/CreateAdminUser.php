<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat akun admin IT baru';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Buat Akun Admin IT ===');

        $name = $this->ask('Nama Admin');
        $email = $this->ask('Email Admin');
        $password = $this->secret('Password (tidak akan ditampilkan)');
        $confirm = $this->secret('Konfirmasi Password');

        if ($password !== $confirm) {
            $this->error('Password tidak sama!');
            return;
        }

        // Cek apakah email sudah ada
        if (User::where('email', $email)->exists()) {
            $this->error('Email ini sudah terdaftar!');
            return;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info('✅ Admin berhasil dibuat!');
        $this->line('Nama : ' . $admin->name);
        $this->line('Email: ' . $admin->email);
        $this->line('Role : ' . $admin->role);
    }
}
