<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\Asset;
use App\Jobs\SendTicketToN8n;
use App\Jobs\SendAssetToN8n;
use Illuminate\Console\Command;

class SyncAllToN8n extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-all-to-n8n {--limit= : Limit per model type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all historical tickets and assets to n8n automation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');

        // Sync Tickets
        $ticketQuery = Ticket::query();
        if ($limit) $ticketQuery->limit($limit);
        $tickets = $ticketQuery->get();
        
        $this->info("Dispatched " . $tickets->count() . " tickets to n8n...");
        foreach ($tickets as $ticket) {
            SendTicketToN8n::dispatch($ticket, 'create');
            // Small delay to prevent hitting Google API rate limits too fast
            usleep(500000); // 0.5 seconds
        }

        // Sync Assets
        $assetQuery = Asset::query();
        if ($limit) $assetQuery->limit($limit);
        $assets = $assetQuery->get();

        $this->info("Dispatched " . $assets->count() . " assets to n8n...");
        foreach ($assets as $asset) {
            SendAssetToN8n::dispatch($asset, 'create');
            usleep(500000); // 0.5 seconds
        }

        $this->info('All sync jobs dispatched successfully.');
        $this->warn('Make sure N8N_WEBHOOK_URL and GOOGLE_SPREADSHEET_ID are set in .env on the server.');
    }
}
