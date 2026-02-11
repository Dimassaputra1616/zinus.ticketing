<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    protected $signature = 'api:generate-token {email}';
    protected $description = 'Generate API token for a user';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        if (!$user->isAdmin()) {
            $this->error("User {$email} is not an admin. Only admins can have API tokens.");
            return 1;
        }

        $tokenName = $this->ask('Token name (e.g., "n8n-integration")', 'api-token');
        
        $token = $user->createToken($tokenName)->plainTextToken;

        $this->info('API Token generated successfully:');
        $this->line('');
        $this->line($token);
        $this->line('');
        $this->warn('IMPORTANT: Save this token now. You won\'t be able to see it again!');

        return 0;
    }
}
