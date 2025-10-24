<?php

namespace App\Console\Commands;

use App\Adapters\Messenger\BaleAdapter;
use Illuminate\Console\Command;

class SetupWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:setup {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup webhook URL for Bale bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $webhookUrl = $this->argument('url');
        
        try {
            $baleAdapter = new BaleAdapter(config('services.bale.token'));
            $result = $baleAdapter->setWebhook($webhookUrl);
            
            $this->info('Webhook URL set successfully!');
            $this->info('URL: ' . $webhookUrl);
            $this->info('Response: ' . json_encode($result, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error('Failed to set webhook URL: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
