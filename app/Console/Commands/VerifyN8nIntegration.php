<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use App\Events\MessageCreated;
use Illuminate\Support\Facades\Log;

class VerifyN8nIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'n8n:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify n8n integration webhooks and API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('--- MEMULAI VERIFIKASI INTEGRASI N8N ---');

        // 1. Check Configuration and Environment Setup
        // Force sync queue driver to avoid needing jobs table/workers
        config(['queue.default' => 'sync']);
        
        $webhookUrl = config('services.n8n.webhook_url');
        if (empty($webhookUrl)) {
            $this->warn('[WARNING] N8N_WEBHOOK_URL belum diset di .env!');
            // Set dummy URL to prevent failure in Http::post
            config(['services.n8n.webhook_url' => 'http://dummy-n8n-webhook.local']);
        } else {
            $this->info("[CONFIG] Webhook URL: {$webhookUrl}");
        }

        // 2. Create Dummy User & Conversation
        $this->info("\n[STEP 1] Membuat Data Dummy...");
        $user = User::factory()->create([
            'name' => 'N8n Verification User',
            'email' => 'n8nverify_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->line("User dummy: {$user->email}");

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'is_open' => true,
        ]);
        $this->line("Conversation ID: {$conversation->id}");


        // 3. Test Outgoing Webhook (via Event)
        $this->info("\n[STEP 2] Test Outgoing Webhook (Laravel -> n8n)...");
        try {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => 'Test message outgoing verification ' . now(),
                'is_read' => false,
            ]);
            
            $this->line("Pesan dibuat (ID: {$message->id}). Mengirim event...");
            
            // Dispatch event manually
            Event::dispatch(new MessageCreated($message, 'user'));
            
            $this->info("[SUCCESS] Event MessageCreated berhasil dikirim.");
            $this->line("Silakan cek n8n Anda untuk melihat apakah webhook diterima.");
            
        } catch (\Exception $e) {
            $this->error("[FAILED] Gagal mengirim event: " . $e->getMessage());
        }

        // 4. Test Incoming API (n8n -> Laravel)
        $this->info("\n[STEP 3] Test Incoming API (n8n -> Laravel)...");
        
        // Create admin user for token if needed, or use existing admin
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['is_admin' => true, 'role' => 'admin']);
            $this->line("Admin dummy dibuat untuk token.");
        } else {
            $this->line("Menggunakan admin: {$admin->email}");
        }

        $tokenName = 'n8n-verification-temp-' . uniqid();
        $token = $admin->createToken($tokenName)->plainTextToken;
        $this->line("Token sementara dibuat.");

        $this->line("Token sementara dibuat.");

        // Self-request to API
        // Try to determine valid URL. default to 127.0.0.1:8000 for local testing
        $baseUrl = 'http://127.0.0.1:8000';
        $apiUrl = "{$baseUrl}/api/v1/conversations/{$conversation->id}/messages";
        $this->line("Mengirim request ke: {$apiUrl} (Pastikan 'php artisan serve' berjalan)");

        try {
            $response = Http::timeout(2)->withToken($token)->post($apiUrl, [
                'body' => 'Test reply incoming verification ' . now(),
            ]);

            if ($response->successful()) {
                $this->info("[SUCCESS] API Incoming berhasil! Status: " . $response->status());
                $data = $response->json();
                $this->line("Response ID: " . ($data['data']['id'] ?? 'unknown'));
            } else {
                $this->error("[FAILED] API Incoming gagal. Status: " . $response->status());
                $this->line("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("[ERROR] Exception saat call API: " . $e->getMessage());
        }

        // Cleanup
        $this->info("\n[STEP 4] Cleanup...");
        // Clean up token
        $admin->tokens()->where('name', $tokenName)->delete();
        $this->line("Token sementara dihapus.");
        
        // Clean up data
        $conversation->delete(); // Cascades messages
        $user->delete();
        $this->line("Data dummy dihapus.");

        $this->info("\nVERIFIKASI SELESAI.");
    }
}
