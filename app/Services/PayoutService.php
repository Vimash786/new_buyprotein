<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\Sellers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Generate payouts for all eligible sellers for a specific period.
     */
    public function generatePayouts($periodStart = null, $periodEnd = null)
    {
        $periodStart = $periodStart ? Carbon::parse($periodStart) : Carbon::now()->subDays(15);
        $periodEnd = $periodEnd ? Carbon::parse($periodEnd) : Carbon::now();
        
        // Get all approved sellers
        $sellers = Sellers::where('status', 'approved')->get();
        
        $generatedPayouts = [];
        
        foreach ($sellers as $seller) {
            // Check if payout already exists for this period
            $existingPayout = Payout::where('seller_id', $seller->id)
                ->where('period_start', $periodStart->toDateString())
                ->where('period_end', $periodEnd->toDateString())
                ->exists();
                
            if ($existingPayout) {
                continue;
            }
            
            $earnings = $seller->calculateEarnings($periodStart, $periodEnd);
            
            // Only create payout if there are sales
            if ($earnings['total_sales'] > 0) {
                $payout = Payout::create([
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->company_name,
                    'total_orders' => $earnings['total_orders'],
                    'total_sales' => $earnings['total_sales'],
                    'commission_amount' => $earnings['commission_amount'],
                    'payout_amount' => $earnings['payout_amount'],
                    'due_date' => Carbon::now()->addDays(5), // Due in 5 days
                    'payout_date' => $this->calculateNextPayoutDate($seller),
                    'payment_status' => 'unpaid',
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);
                
                $generatedPayouts[] = $payout;
            }
        }
        
        return $generatedPayouts;
    }

    /**
     * Generate automatic payouts for sellers whose next payout date has passed.
     */
    public function generateAutomaticPayouts($force = false)
    {
        $now = Carbon::now();
        $generatedPayouts = [];
        
        // Get all approved sellers
        $sellers = Sellers::where('status', 'approved')->get();
        
        foreach ($sellers as $seller) {
            // Set initial next payout date if not set
            if (!$seller->next_payout_date) {
                $seller->next_payout_date = $this->calculateInitialPayoutDate($seller);
                $seller->save();
            }
            
            // Convert to Carbon instance if it's not already
            $nextPayoutDate = Carbon::parse($seller->next_payout_date);
            
            // Check if payout is due (or force flag is used)
            if ($force || $now->gte($nextPayoutDate)) {
                $periodEnd = $nextPayoutDate;
                $periodStart = $periodEnd->copy()->subDays(15);
                
                // Check if payout already exists for this period
                $existingPayout = Payout::where('seller_id', $seller->id)
                    ->where('period_start', $periodStart->toDateString())
                    ->where('period_end', $periodEnd->toDateString())
                    ->exists();
                    
                if ($existingPayout) {
                    // Update next payout date for next cycle
                    $seller->next_payout_date = $periodEnd->copy()->addDays(15);
                    $seller->save();
                    continue;
                }
                
                $earnings = $seller->calculateEarnings($periodStart, $periodEnd);
                
                // Only create payout if there are sales
                if ($earnings['total_sales'] > 0) {
                    $payout = Payout::create([
                        'seller_id' => $seller->id,
                        'seller_name' => $seller->company_name,
                        'total_orders' => $earnings['total_orders'],
                        'total_sales' => $earnings['total_sales'],
                        'commission_amount' => $earnings['commission_amount'],
                        'payout_amount' => $earnings['payout_amount'],
                        'due_date' => $periodEnd->copy()->addDays(5), // Due in 5 days
                        'payout_date' => $periodEnd->copy()->addDays(15), // Payout date 15 days later
                        'payment_status' => 'unpaid',
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'notes' => 'Auto-generated payout for 15-day period',
                    ]);
                    
                    $generatedPayouts[] = $payout;
                }
                
                // Update next payout date for next cycle
                $seller->next_payout_date = $periodEnd->copy()->addDays(15);
                $seller->save();
            }
        }
        
        return $generatedPayouts;
    }

    /**
     * Calculate initial payout date for a seller (15 days from approval/registration).
     */
    private function calculateInitialPayoutDate($seller)
    {
        // Use the seller's created_at date as the base
        $baseDate = $seller->created_at;
        $now = Carbon::now();
        
        // Calculate how many 15-day periods have passed since registration
        $daysSinceRegistration = $baseDate->diffInDays($now);
        $periodsElapsed = floor($daysSinceRegistration / 15);
        
        // Calculate the next payout date
        return $baseDate->copy()->addDays(($periodsElapsed + 1) * 15);
    }
    
    /**
     * Calculate next payout date based on seller registration/approval date.
     */
    private function calculateNextPayoutDate($seller)
    {
        // Assuming the seller has an approved_at or created_at date
        $baseDate = $seller->updated_at ?? $seller->created_at;
        $now = Carbon::now();
        
        // Calculate 15-day intervals from base date
        $daysSinceBase = $baseDate->diffInDays($now);
        $intervals = ceil($daysSinceBase / 15);
        
        return $baseDate->copy()->addDays($intervals * 15);
    }
    
    /**
     * Mark payout as paid and create transaction record.
     */
    public function markAsPaid($payoutId, $transactionData)
    {
        DB::beginTransaction();
        
        try {
            $payout = Payout::findOrFail($payoutId);
            
            // Update payout status
            $payout->update([
                'payment_status' => 'paid',
                'updated_at' => now()
            ]);
            
            // Create transaction record
            $payout->transactions()->create([
                'payment_method' => $transactionData['payment_method'],
                'transaction_date' => $transactionData['transaction_date'] ?? now(),
                'amount' => $payout->payout_amount,
                'notes' => $transactionData['notes'] ?? null,
                'reference_number' => $transactionData['reference_number'] ?? null,
                'bank_details' => $transactionData['bank_details'] ?? null,
                'upi_details' => $transactionData['upi_details'] ?? null,
                'wallet_details' => $transactionData['wallet_details'] ?? null,
                'status' => 'completed'
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Get payout statistics.
     */
    public function getPayoutStats()
    {
        return [
            'total_payouts' => Payout::count(),
            'paid_payouts' => Payout::where('payment_status', 'paid')->count(),
            'unpaid_payouts' => Payout::where('payment_status', 'unpaid')->count(),
            'overdue_payouts' => Payout::overdue()->count(),
            'due_soon_payouts' => Payout::dueSoon()->count(),
            'total_amount_paid' => Payout::where('payment_status', 'paid')->sum('payout_amount'),
            'total_amount_pending' => Payout::where('payment_status', 'unpaid')->sum('payout_amount'),
        ];
    }
    
    /**
     * Get seller payout summary.
     */
    public function getSellerPayoutSummary($sellerId, $limit = 10)
    {
        return Payout::where('seller_id', $sellerId)
            ->with('transactions')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
