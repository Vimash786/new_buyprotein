<?php

use App\Models\PayoutTransaction;
use App\Models\Payout;
use App\Models\Sellers;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $paymentMethodFilter = '';
    public $sellerFilter = '';
    public $dateFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    
    // Modals
    public $showViewModal = false;
    public $showEditModal = false;
    
    // Selected records
    public $viewingTransaction = null;
    public $editingTransaction = null;
    
    // Edit form
    public $editStatus = '';
    public $editNotes = '';
    public $editReferenceNumber = '';
    
    protected $queryString = ['search', 'statusFilter', 'paymentMethodFilter', 'sellerFilter', 'dateFilter'];
    
    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }
    
    public function with()
    {
        $query = PayoutTransaction::with(['payout.seller']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('transaction_id', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%')
                  ->orWhereHas('payout', function($payout) {
                      $payout->where('payout_id', 'like', '%' . $this->search . '%')
                             ->orWhere('seller_name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }
        
        if ($this->sellerFilter) {
            $query->whereHas('payout', function($payout) {
                $payout->where('seller_id', $this->sellerFilter);
            });
        }
        
        if ($this->dateFilter) {
            match($this->dateFilter) {
                'today' => $query->whereDate('transaction_date', today()),
                'week' => $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('transaction_date', now()->month),
                'custom' => $query->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo]),
                default => null
            };
        }
        
        // Get statistics
        $stats = [
            'total_transactions' => PayoutTransaction::count(),
            'completed_transactions' => PayoutTransaction::where('status', 'completed')->count(),
            'pending_transactions' => PayoutTransaction::where('status', 'pending')->count(),
            'failed_transactions' => PayoutTransaction::where('status', 'failed')->count(),
            'total_amount_processed' => PayoutTransaction::where('status', 'completed')->sum('amount'),
            'total_amount_pending' => PayoutTransaction::where('status', 'pending')->sum('amount'),
        ];
        
        return [
            'transactions' => $query->latest('transaction_date')->paginate(15),
            'sellers' => Sellers::where('status', 'approved')->get(),
            'stats' => $stats,
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
    
    public function updatingPaymentMethodFilter()
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
    
    // View transaction details
    public function viewTransaction($id)
    {
        $this->viewingTransaction = PayoutTransaction::with(['payout.seller'])->findOrFail($id);
        $this->showViewModal = true;
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingTransaction = null;
    }
    
    // Edit transaction
    public function editTransaction($id)
    {
        $this->editingTransaction = PayoutTransaction::findOrFail($id);
        $this->editStatus = $this->editingTransaction->status;
        $this->editNotes = $this->editingTransaction->notes;
        $this->editReferenceNumber = $this->editingTransaction->reference_number;
        $this->showEditModal = true;
    }
    
    public function updateTransaction()
    {
        $this->validate([
            'editStatus' => 'required|in:completed,pending,failed,cancelled',
            'editNotes' => 'nullable|string|max:1000',
            'editReferenceNumber' => 'nullable|string|max:255',
        ]);
        
        $this->editingTransaction->update([
            'status' => $this->editStatus,
            'notes' => $this->editNotes,
            'reference_number' => $this->editReferenceNumber,
        ]);
        
        session()->flash('message', 'Transaction updated successfully!');
        $this->closeEditModal();
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingTransaction = null;
        $this->resetEditForm();
    }
    
    private function resetEditForm()
    {
        $this->editStatus = '';
        $this->editNotes = '';
        $this->editReferenceNumber = '';
    }
    
    public function exportTransactions()
    {
        // TODO: Implement export functionality
        session()->flash('message', 'Export functionality will be implemented soon!');
    }
    
    public function applyDateFilter()
    {
        $this->dateFilter = 'custom';
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Transaction Management</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">View and manage all payout transactions</p>
                </div>
                <div class="flex space-x-3">
                    <button 
                        wire:click="exportTransactions"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Transactions
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_transactions']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Completed</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['completed_transactions']) }}</p>
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
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['pending_transactions']) }}</p>
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
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Failed</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['failed_transactions']) }}</p>
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
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input 
                        type="text" 
                        wire:model.live="search"
                        placeholder="Transaction ID, Reference..."
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
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                    <select 
                        wire:model.live="paymentMethodFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    >
                        <option value="">All Methods</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="upi">UPI</option>
                        <option value="wallet">Wallet</option>
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
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="flex items-end">
                    <button 
                        wire:click="$set('search', ''); $set('statusFilter', ''); $set('paymentMethodFilter', ''); $set('sellerFilter', ''); $set('dateFilter', '')"
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Custom Date Range -->
            @if($dateFilter === 'custom')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input 
                            type="date" 
                            wire:model="dateFrom"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input 
                            type="date" 
                            wire:model="dateTo"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                        >
                    </div>
                    <div class="flex items-end">
                        <button 
                            wire:click="applyDateFilter"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium"
                        >
                            Apply Date Filter
                        </button>
                    </div>
                </div>
            @endif
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

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Transaction ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payout</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $transaction->transaction_id }}
                                    @if($transaction->reference_number)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Ref: {{ $transaction->reference_number }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->payout->payout_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->payout->seller_name }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">#S{{ $transaction->payout->seller_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($transaction->payment_method === 'bank_transfer')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            @elseif($transaction->payment_method === 'upi')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            @elseif($transaction->payment_method === 'wallet')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            @endif
                                        </svg>
                                        {{ $transaction->payment_method_display }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">
                                    ₹{{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->transaction_date->format('M d, Y') }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->transaction_date->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $transaction->status_color }}-100 dark:bg-{{ $transaction->status_color }}-900/50 
                                           text-{{ $transaction->status_color }}-800 dark:text-{{ $transaction->status_color }}-300">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            wire:click="viewTransaction({{ $transaction->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        
                                        <button 
                                            wire:click="editTransaction({{ $transaction->id }})"
                                            class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300"
                                            title="Edit Transaction"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No transactions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <!-- View Transaction Modal -->
    @if($showViewModal && $viewingTransaction)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Transaction Details
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Transaction Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Transaction Info</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Transaction ID:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->transaction_id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Payout ID:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->payout->payout_id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Amount:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">₹{{ number_format($viewingTransaction->amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Date:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->transaction_date->format('M d, Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Status:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $viewingTransaction->status_color }}-100 dark:bg-{{ $viewingTransaction->status_color }}-900/50 
                                           text-{{ $viewingTransaction->status_color }}-800 dark:text-{{ $viewingTransaction->status_color }}-300">
                                        {{ ucfirst($viewingTransaction->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Seller & Payment Info</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Seller:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->payout->seller_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Payment Method:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->payment_method_display }}</span>
                                </div>
                                @if($viewingTransaction->reference_number)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-300">Reference:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewingTransaction->reference_number }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    @if($viewingTransaction->bank_details || $viewingTransaction->upi_details || $viewingTransaction->wallet_details)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Details</h3>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                @if($viewingTransaction->bank_details)
                                    @php $bankDetails = json_decode($viewingTransaction->bank_details, true); @endphp
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">Account Number:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $bankDetails['account_number'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">IFSC Code:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $bankDetails['ifsc_code'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">Bank Name:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $bankDetails['bank_name'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">Account Holder:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $bankDetails['account_holder'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if($viewingTransaction->upi_details)
                                    @php $upiDetails = json_decode($viewingTransaction->upi_details, true); @endphp
                                    <div>
                                        <span class="text-sm text-gray-600 dark:text-gray-300">UPI ID:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $upiDetails['upi_id'] ?? 'N/A' }}</p>
                                    </div>
                                @endif

                                @if($viewingTransaction->wallet_details)
                                    @php $walletDetails = json_decode($viewingTransaction->wallet_details, true); @endphp
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">Wallet Type:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($walletDetails['wallet_type'] ?? 'N/A') }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">Wallet ID:</span>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $walletDetails['wallet_id'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($viewingTransaction->notes)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Notes</h3>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                <p class="text-gray-900 dark:text-white">{{ $viewingTransaction->notes }}</p>
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

    <!-- Edit Transaction Modal -->
    @if($showEditModal && $editingTransaction)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Edit Transaction</h2>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="updateTransaction" class="space-y-4">
                        <!-- Transaction Info Display -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 mb-4">
                            <div class="text-sm text-gray-600 dark:text-gray-300">Transaction ID</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $editingTransaction->transaction_id }}</div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select 
                                wire:model="editStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                required
                            >
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('editStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Reference Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference Number</label>
                            <input 
                                type="text" 
                                wire:model="editReferenceNumber"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Transaction reference number"
                            >
                            @error('editReferenceNumber') <span class="text-red-500 text-sm">@enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <textarea 
                                wire:model="editNotes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Additional notes about the transaction..."
                            ></textarea>
                            @error('editNotes') <span class="text-red-500 text-sm">@enderror
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                Update Transaction
                            </button>
                            <button 
                                type="button"
                                wire:click="closeEditModal"
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
</div>
