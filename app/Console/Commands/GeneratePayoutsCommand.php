<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GeneratePayoutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:generate {--force : Force generation even if not due}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate payouts for sellers every 15 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic payout generation...');
        
        $payoutService = new PayoutService();
        
        try {
            $generatedPayouts = $payoutService->generateAutomaticPayouts($this->option('force'));
            
            if (empty($generatedPayouts)) {
                $this->info('No payouts were generated. All sellers are up to date.');
                return 0;
            }
            
            $this->info('Generated ' . count($generatedPayouts) . ' payouts:');
            
            foreach ($generatedPayouts as $payout) {
                $this->line("- {$payout->seller_name}: ₹" . number_format($payout->payout_amount, 2) . " ({$payout->payout_id})");
            }
            
            $this->info('Automatic payout generation completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error generating payouts: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
