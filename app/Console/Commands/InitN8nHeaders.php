<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitN8nHeaders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-n8n-headers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize all monthly tabs and headers in Google Sheets via n8n';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $webhookUrl = config('services.n8n.webhook_url');
        
        if (empty($webhookUrl)) {
            $this->error('N8N_WEBHOOK_URL is not configured.');
            return 1;
        }

        $headers = ['Ticket ID', 'Ticket', 'Catagory', 'Assigned To', 'Created Ticket', 'Status'];
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June', 
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        $year = now()->year;

        $this->info("Initializing 12 monthly tabs for year $year...");

        foreach ($months as $month) {
            $tabName = "$month $year";
            $this->comment("Sending init for $tabName...");

            // Prepare a "Dummy" payload where values = headers
            // This forces n8n to create the sheet and write these as row 1
            $payload = [
                'action' => 'init',
                'target_tab' => $tabName,
                'sheet_name' => "Tickets - $tabName",
                'Ticket ID' => 'Ticket ID',
                'Ticket' => 'Ticket',
                'Catagory' => 'Catagory',
                'Assigned To' => 'Assigned To',
                'Created Ticket' => 'Created Ticket',
                'Status' => 'Status',
            ];

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->post($webhookUrl, $payload);
                
                if ($response->successful()) {
                    $this->info("Successfully initialized $tabName");
                } else {
                    $this->error("Failed for $tabName. Status: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->error("Error for $tabName: " . $e->getMessage());
            }

            // Small sleep to avoid n8n/Google API saturation
            usleep(800000); 
        }

        $this->info('Initialization complete! Check your Google Sheet.');
        return 0;
    }
}
