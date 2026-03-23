<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Jobs\SendTicketToN8n;
use Illuminate\Console\Command;

class SyncTicketsToN8n extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:sync-n8n {--limit= : Limit the number of tickets to sync} {--id= : Sync a specific ticket ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync historical tickets to n8n automation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        $limit = $this->option('limit');

        $query = Ticket::query();

        if ($id) {
            $query->where('id', $id);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $tickets = $query->get();
        $count = $tickets->count();

        if ($count === 0) {
            $this->info('No tickets found to sync.');
            return;
        }

        $this->info("Starting sync of {$count} tickets to n8n...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($tickets as $ticket) {
            SendTicketToN8n::dispatch($ticket, 'sync');
            $bar->advance();
            
            // Add a larger delay (2 seconds) to ensure we don't hit Google Sheets API rate limits
            sleep(2); 
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sync jobs dispatched successfully.');
    }
}
