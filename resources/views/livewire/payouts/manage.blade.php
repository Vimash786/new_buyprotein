<?php

use App\Models\Payout;
use App\Models\PayoutTransaction;
use App\Models\Sellers;
use App\Services\PayoutService;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $sellerFilter = '';
    public $dateFilter = '';
    
    // Modals
    public $showPayoutModal = false;
    public $showPaymentModal = false;
    public $showViewModal = false;
    public $showGenerateModal = false;
    
    // Selected records
    public $selectedPayout = null;
    public $viewingPayout = null;
    
    // Payment form
    public $paymentMethod = 'bank_transfer';
    public $transactionDate = '';
    public $referenceNumber = '';
    public $notes = '';
    public $bankDetails = [];
    public $upiDetails = [];
    public $walletDetails = [];
    
    // Generate payout form
    public $generatePeriodStart = '';
    public $generatePeriodEnd = '';
    
    protected $queryString = ['search', 'statusFilter', 'sellerFilter', 'dateFilter'];
    
    public function mount()
    {
        $this->transactionDate = now()->format('Y-m-d');
        $this->generatePeriodStart = now()->subDays(15)->format('Y-m-d');
        $this->generatePeriodEnd = now()->format('Y-m-d');
    }
    
    public function with()
    {
        $query = Payout::with(['seller', 'transactions']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('payout_id', 'like', '%' . $this->search . '%')
                  ->orWhere('seller_name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('seller', function($seller) {
                      $seller->where('company_name', 'like', '%' . $this->search . '%')
                             ->orWhere('gst_number', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        if ($this->statusFilter) {
            $query->where('payment_status', $this->statusFilter);
        }
        
        if ($this->sellerFilter) {
            $query->where('seller_id', $this->sellerFilter);
        }
        
        if ($this->dateFilter) {
            match($this->dateFilter) {
                'today' => $query->whereDate('created_at', today()),
                'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('created_at', now()->month),
                'overdue' => $query->overdue(),
                'due_soon' => $query->dueSoon(),
                default => null
            };
        }
        
        $payoutService = new PayoutService();
        
        return [
            'payouts' => $query->latest()->paginate(15),
            'sellers' => Sellers::where('status', 'approved')->get(),
            'sellersWithNextPayout' => Sellers::where('status', 'approved')
                ->whereNotNull('next_payout_date')
                ->orderBy('next_payout_date', 'asc')
                ->get(),
            'stats' => $payoutService->getPayoutStats(),
        ];
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingSellerFilter()
    {
        $this->resetPage();
    }
    
    public function updatingDateFilter()
    {
        $this->resetPage();
    }
    
    // Generate new payouts
    public function openGenerateModal()
    {
        $this->showGenerateModal = true;
    }
    
    public function closeGenerateModal()
    {
        $this->showGenerateModal = false;
        $this->resetGenerateForm();
    }
    
    public function generatePayouts()
    {
        $this->validate([
            'generatePeriodStart' => 'required|date',
            'generatePeriodEnd' => 'required|date|after:generatePeriodStart',
        ]);
        
        $payoutService = new PayoutService();
        $generatedPayouts = $payoutService->generatePayouts(
            $this->generatePeriodStart,
            $this->generatePeriodEnd
        );
        
        session()->flash('message', count($generatedPayouts) . ' payouts generated successfully!');
        $this->closeGenerateModal();
    }
    
    private function resetGenerateForm()
    {
        $this->generatePeriodStart = now()->subDays(15)->format('Y-m-d');
        $this->generatePeriodEnd = now()->format('Y-m-d');
    }
    
    // View payout details
    public function viewPayout($id)
    {
        $this->viewingPayout = Payout::with(['seller', 'transactions'])->findOrFail($id);
        $this->showViewModal = true;
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingPayout = null;
    }
    
    // Payment processing
    public function openPaymentModal($id)
    {
        $this->selectedPayout = Payout::findOrFail($id);
        $this->showPaymentModal = true;
        $this->resetPaymentForm();
    }
    
    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->selectedPayout = null;
        $this->resetPaymentForm();
    }
    
    public function processPayment()
    {
        $rules = [
            'paymentMethod' => 'required|in:bank_transfer,upi,wallet',
            'transactionDate' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'referenceNumber' => 'nullable|string|max:255',
        ];
        
        // Add specific validation based on payment method
        if ($this->paymentMethod === 'bank_transfer') {
            $rules = array_merge($rules, [
                'bankDetails.account_number' => 'required|string',
                'bankDetails.bank_name' => 'required|string',
                'bankDetails.ifsc_code' => 'required|string',
                'bankDetails.account_holder' => 'required|string',
            ]);
        } elseif ($this->paymentMethod === 'upi') {
            $rules['upiDetails.upi_id'] = 'required|string';
        } elseif ($this->paymentMethod === 'wallet') {
            $rules = array_merge($rules, [
                'walletDetails.wallet_type' => 'required|string',
                'walletDetails.wallet_id' => 'required|string',
            ]);
        }
        
        $this->validate($rules);
        
        try {
            $transactionData = [
                'payment_method' => $this->paymentMethod,
                'transaction_date' => $this->transactionDate,
                'notes' => $this->notes,
                'reference_number' => $this->referenceNumber,
            ];
            
            if ($this->paymentMethod === 'bank_transfer') {
                $transactionData['bank_details'] = $this->bankDetails;
            } elseif ($this->paymentMethod === 'upi') {
                $transactionData['upi_details'] = $this->upiDetails;
            } elseif ($this->paymentMethod === 'wallet') {
                $transactionData['wallet_details'] = $this->walletDetails;
            }
            
            $payoutService = new PayoutService();
            $payoutService->markAsPaid($this->selectedPayout->id, $transactionData);
            
            session()->flash('message', 'Payment processed successfully!');
            $this->closePaymentModal();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error processing payment: ' . $e->getMessage());
        }
    }
    
    private function resetPaymentForm()
    {
        $this->paymentMethod = 'bank_transfer';
        $this->transactionDate = now()->format('Y-m-d');
        $this->referenceNumber = '';
        $this->notes = '';
        $this->bankDetails = [];
        $this->upiDetails = [];
        $this->walletDetails = [];
    }
    
    public function exportPayouts()
    {
        // TODO: Implement export functionality
        session()->flash('message', 'Export functionality will be implemented soon!');
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Seller Payouts</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Automatic payouts are generated every 15 days for each seller. 
                        Manual generation is available for custom periods.
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button 
                        wire:click="exportPayouts"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                    <button 
                        wire:click="openGenerateModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Manual Generation
                    </button>
                </div>
            </div>
        </div>

        <!-- Auto-Generation Info Banner -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-8">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        Automatic Payout Generation
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                        <p>• Payouts are automatically generated every 15 days for each seller</p>
                        <p>• First payout is scheduled 15 days after seller approval</p>
                        <p>• System runs daily at 9:00 AM to check for due payouts</p>
                        <p>• Only periods with sales will generate payouts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Payout Schedule -->
        @if($sellersWithNextPayout->isNotEmpty())
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Automatic Payouts</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Next scheduled payout dates for sellers</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Next Payout Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Days Until Due</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($sellersWithNextPayout->take(10) as $seller)
                                @php
                                    $nextPayoutDate = \Carbon\Carbon::parse($seller->next_payout_date);
                                    $daysUntilDue = $nextPayoutDate->diffInDays(now(), false);
                                    $isDue = $daysUntilDue <= 0;
                                    $isComingSoon = $daysUntilDue > 0 && $daysUntilDue <= 3;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $seller->company_name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">#S{{ $seller->id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $nextPayoutDate->format('M d, Y') }}
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $nextPayoutDate->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if($isDue)
                                            <span class="text-red-600 dark:text-red-400 font-medium">Due now</span>
                                        @elseif($isComingSoon)
                                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ abs($daysUntilDue) }} days</span>
                                        @else
                                            <span class="text-gray-600 dark:text-gray-400">{{ abs($daysUntilDue) }} days</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($isDue)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                                                Due
                                            </span>
                                        @elseif($isComingSoon)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                                Soon
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300">
                                                Scheduled
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Payouts</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_payouts']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Paid Payouts</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['paid_payouts']) }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-full">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Pending Payouts</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['unpaid_payouts']) }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Overdue</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['overdue_payouts']) }}</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-full">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input 
                        type="text" 
                        wire:model.live="search"
                        placeholder="Search payouts, sellers..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    >
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select 
                        wire:model.live="statusFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    >
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="processing">Processing</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Seller Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seller</label>
                    <select 
                        wire:model.live="sellerFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    >
                        <option value="">All Sellers</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->company_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                    <select 
                        wire:model.live="dateFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    >
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="overdue">Overdue</option>
                        <option value="due_soon">Due Soon</option>
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="flex items-end">
                    <button 
                        wire:click="$set('search', ''); $set('statusFilter', ''); $set('sellerFilter', ''); $set('dateFilter', '')"
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Payouts Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payout ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payout Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($payouts as $payout)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $payout->payout_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->seller_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">#S{{ $payout->seller_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $payout->period_display }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($payout->total_orders) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ₹{{ number_format($payout->total_sales, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ₹{{ number_format($payout->commission_amount, 2) }}
                                    <div class="text-xs text-gray-500">({{ number_format($payout->commission_percentage, 1) }}%)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">
                                    ₹{{ number_format($payout->payout_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $payout->due_date->format('M d, Y') }}
                                    @if($payout->isOverdue())
                                        <div class="text-xs text-red-500">(Overdue)</div>
                                    @elseif($payout->isDueSoon())
                                        <div class="text-xs text-yellow-500">(Due Soon)</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $payout->status_color }}-100 dark:bg-{{ $payout->status_color }}-900/50 
                                           text-{{ $payout->status_color }}-800 dark:text-{{ $payout->status_color }}-300">
                                        {{ $payout->status_text }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            wire:click="viewPayout({{ $payout->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        
                                        @if($payout->payment_status === 'unpaid')
                                            <button 
                                                wire:click="openPaymentModal({{ $payout->id }})"
                                                class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                                title="Process Payment"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No payouts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $payouts->links() }}
            </div>
        </div>
    </div>

    <!-- Generate Payouts Modal -->
    @if($showGenerateModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manual Payout Generation</h2>
                        <button wire:click="closeGenerateModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                            <strong>Note:</strong> This is for manual generation only. The system automatically generates payouts every 15 days for each seller.
                        </p>
                    </div>

                    <form wire:submit="generatePayouts" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period Start Date</label>
                            <input 
                                type="date" 
                                wire:model="generatePeriodStart"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                required
                            >
                            @error('generatePeriodStart') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period End Date</label>
                            <input 
                                type="date" 
                                wire:model="generatePeriodEnd"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                required
                            >
                            @error('generatePeriodEnd') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                Generate Payouts
                            </button>
                            <button 
                                type="button"
                                wire:click="closeGenerateModal"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Modal -->
    @if($showPaymentModal && $selectedPayout)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Process Payment</h2>
                        <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Payout Summary -->
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 mb-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Payout Details</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 dark:text-gray-300">Payout ID:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $selectedPayout->payout_id }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-300">Seller:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $selectedPayout->seller_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-300">Amount:</span>
                                <span class="font-semibold text-green-600 dark:text-green-400 ml-2">₹{{ number_format($selectedPayout->payout_amount, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-300">Due Date:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $selectedPayout->due_date->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="processPayment" class="space-y-4">
                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                            <select 
                                wire:model.live="paymentMethod"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                required
                            >
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="wallet">Wallet</option>
                            </select>
                            @error('paymentMethod') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Bank Transfer Details -->
                        @if($paymentMethod === 'bank_transfer')
                            <div class="space-y-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white">Bank Transfer Details</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number</label>
                                        <input 
                                            type="text" 
                                            wire:model="bankDetails.account_number"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                        @error('bankDetails.account_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IFSC Code</label>
                                        <input 
                                            type="text" 
                                            wire:model="bankDetails.ifsc_code"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                        @error('bankDetails.ifsc_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Name</label>
                                        <input 
                                            type="text" 
                                            wire:model="bankDetails.bank_name"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                        @error('bankDetails.bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Holder</label>
                                        <input 
                                            type="text" 
                                            wire:model="bankDetails.account_holder"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                        @error('bankDetails.account_holder') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- UPI Details -->
                        @if($paymentMethod === 'upi')
                            <div class="space-y-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white">UPI Details</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">UPI ID</label>
                                    <input 
                                        type="text" 
                                        wire:model="upiDetails.upi_id"
                                        placeholder="example@upi"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        required
                                    >
                                    @error('upiDetails.upi_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        <!-- Wallet Details -->
                        @if($paymentMethod === 'wallet')
                            <div class="space-y-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white">Wallet Details</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wallet Type</label>
                                        <select 
                                            wire:model="walletDetails.wallet_type"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                            <option value="">Select Wallet</option>
                                            <option value="paytm">Paytm</option>
                                            <option value="phonepe">PhonePe</option>
                                            <option value="gpay">Google Pay</option>
                                            <option value="amazonpay">Amazon Pay</option>
                                        </select>
                                        @error('walletDetails.wallet_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wallet ID</label>
                                        <input 
                                            type="text" 
                                            wire:model="walletDetails.wallet_id"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            required
                                        >
                                        @error('walletDetails.wallet_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Common Fields -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transaction Date</label>
                                <input 
                                    type="date" 
                                    wire:model="transactionDate"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    required
                                >
                                @error('transactionDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference Number</label>
                                <input 
                                    type="text" 
                                    wire:model="referenceNumber"
                                    placeholder="Transaction reference"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                @error('referenceNumber') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <textarea 
                                wire:model="notes"
                                rows="3"
                                placeholder="Additional notes about the payment..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            ></textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                Process Payment
                            </button>
                            <button 
                                type="button"
                                wire:click="closePaymentModal"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- View Payout Modal -->
    @if($showViewModal && $viewingPayout)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Payout Details - {{ $viewingPayout->payout_id }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Payout Information -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payout Information</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Payout ID:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingPayout->payout_id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Seller:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingPayout->seller_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Period:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingPayout->period_display }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Status:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $viewingPayout->status_color }}-100 dark:bg-{{ $viewingPayout->status_color }}-900/50 
                                           text-{{ $viewingPayout->status_color }}-800 dark:text-{{ $viewingPayout->status_color }}-300">
                                        {{ $viewingPayout->status_text }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Due Date:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingPayout->due_date->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Payout Date:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingPayout->payout_date->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Financial Summary</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Total Orders:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($viewingPayout->total_orders) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Total Sales:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">₹{{ number_format($viewingPayout->total_sales, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Commission ({{ number_format($viewingPayout->commission_percentage, 1) }}%):</span>
                                    <span class="font-medium text-orange-600 dark:text-orange-400">₹{{ number_format($viewingPayout->commission_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-semibold">
                                    <span class="text-gray-900 dark:text-white">Payout Amount:</span>
                                    <span class="text-green-600 dark:text-green-400">₹{{ number_format($viewingPayout->payout_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    @if($viewingPayout->transactions->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Transaction History</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Transaction ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Method</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Amount</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                        @foreach($viewingPayout->transactions as $transaction)
                                            <tr class="bg-white dark:bg-zinc-700">
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $transaction->transaction_id }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $transaction->payment_method_icon }}" />
                                                        </svg>
                                                        {{ $transaction->payment_method_display }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">₹{{ number_format($transaction->amount, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $transaction->transaction_date->format('M d, Y H:i') }}</td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                           bg-{{ $transaction->status_color }}-100 dark:bg-{{ $transaction->status_color }}-900/50 
                                                           text-{{ $transaction->status_color }}-800 dark:text-{{ $transaction->status_color }}-300">
                                                        {{ ucfirst($transaction->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($viewingPayout->notes)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Notes</h3>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                <p class="text-gray-900 dark:text-white">{{ $viewingPayout->notes }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Close Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            wire:click="closeViewModal"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
