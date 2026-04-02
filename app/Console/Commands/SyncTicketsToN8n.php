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
    protected $signature = 'tickets:sync-n8n 
                            {--limit= : Limit the number of tickets to sync} 
                            {--id= : Sync a specific ticket ID}
                            {--month= : Month number (01-12) to sync}
                            {--year= : Year (e.g. 2026) to sync}
                            {--all : Sync all tickets}
                            {--dry-run : Only show how many tickets would be synced}
                            {--delay=2 : Delay in seconds between jobs to avoid rate limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync historical tickets to n8n automation with monthly filtering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        $limit = $this->option('limit');
        $month = $this->option('month');
        $year = $this->option('year');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');
        $delay = (int) $this->option('delay');

        $query = Ticket::query();

        if ($id) {
            $query->where('id', $id);
        } elseif ($month || $year) {
            if ($month) $query->whereMonth('created_at', $month);
            if ($year) $query->whereYear('created_at', $year);
        } elseif (!$all) {
            $this->error('Please specify --all, --id, or --month/--year filtering.');
            return 1;
        }

        if ($limit) {
            $query->limit($limit);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No tickets found to sync.');
            return 0;
        }

        if ($dryRun) {
            $this->info("Dry run: {$count} tickets would be synced to n8n.");
            return 0;
        }

        if (!$this->confirm("Are you sure you want to sync {$count} tickets to n8n?", true)) {
            return 0;
        }

        $this->info("Starting sync of {$count} tickets to n8n...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunk(100, function ($tickets) use ($bar, $delay) {
            foreach ($tickets as $ticket) {
                SendTicketToN8n::dispatch($ticket, 'sync');
                $bar->advance();
                
                if ($delay > 0) {
                    sleep($delay);
                }
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Sync jobs dispatched successfully.');
        
        return 0;
    }
}
