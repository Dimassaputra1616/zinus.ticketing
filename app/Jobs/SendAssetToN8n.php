<?php

namespace App\Jobs;

use App\Models\Asset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAssetToN8n implements ShouldQueue
{
    use Queueable;

    protected $asset;
    protected $action;

    /**
     * Create a new job instance.
     */
    public function __construct(Asset $asset, string $action = 'created')
    {
        $this->asset = $asset;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('N8N_WEBHOOK_URL is not configured. Skipping asset webhook dispatch.');
            return;
        }

        try {
            $this->asset->loadMissing(['category', 'categoryRel', 'department', 'user']);

            $payload = [
                'type' => 'asset',
                'action' => $this->action,
                'asset_id' => $this->asset->id,
                'asset_code' => $this->asset->asset_code,
                'name' => $this->asset->name,
                'hostname' => $this->asset->hostname,
                'status' => strtoupper($this->asset->status),
                'category' => $this->asset->categoryRel ? $this->asset->categoryRel->name : ($this->asset->category ?: '-'),
                'department' => $this->asset->department ? $this->asset->department->name : '-',
                'user' => $this->asset->user ? $this->asset->user->name : 'Unassigned',
                'brand' => $this->asset->brand,
                'model' => $this->asset->model,
                'serial_number' => $this->asset->serial_number,
                
                // Matches Google Sheets expectations
                'Asset Code' => $this->asset->asset_code,
                'Asset Name' => $this->asset->name,
                'Status' => strtoupper($this->asset->status),
                'User' => $this->asset->user ? $this->asset->user->name : 'Unassigned',
                
                'spreadsheet_id' => config('services.n8n.google_spreadsheet_id'),
                'timestamp' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error("Failed to send asset #{$this->asset->id} to n8n. Status: {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending asset #{$this->asset->id} to n8n: " . $e->getMessage());
        }
    }
}
