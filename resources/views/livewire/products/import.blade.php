<?php

use App\Imports\ProductsExcelImport;
use App\Models\Sellers;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Jobs\ImportProductsFromExcel;

new class extends Component
{
	use WithFileUploads;

	public $file;
	public $seller_id = '';
	public $isImporting = false;
	public $messageText = '';
	public $progressId = '';
	public $progress = null;

	protected function rules()
	{
		return [
			'seller_id' => 'required|exists:sellers,id',
			'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
		];
	}

	public function with()
	{
		return [
			'sellers' => Sellers::where('status', 'approved')->get(),
		];
	}

	public function import()
	{
		$this->validate();
		$this->isImporting = true;
		$this->messageText = '';

		// Increase execution time for large imports (no queue worker needed)
		set_time_limit(600); // 10 minutes max
		ini_set('max_execution_time', '600');

		try {
			// Store on 'local' disk which points to storage/app/private
			$stored = $this->file->storeAs('private/imports', Str::uuid()->toString() . '.' . $this->file->getClientOriginalExtension(), 'local');
			$this->progressId = Str::uuid()->toString();
			// Initialize progress immediately
			Cache::put("import:{$this->progressId}", [
				'status' => 'running',
				'total' => 0,
				'processed' => 0,
				'percent' => 0,
				'updated_at' => now()->toDateTimeString(),
			], now()->addHours(2));
			$this->progress = Cache::get("import:{$this->progressId}");
			
			// Run synchronously - processes immediately, no queue worker needed
			ImportProductsFromExcel::dispatchSync($stored, (int)$this->seller_id, $this->progressId, 'local');
			
			// Refresh progress after sync execution
			$this->progress = Cache::get("import:{$this->progressId}");
			$this->isImporting = false;
			
			if ($this->progress && ($this->progress['status'] ?? '') === 'completed') {
				$total = $this->progress['total'] ?? 0;
				$processed = $this->progress['processed'] ?? 0;
				session()->flash('message', "Import completed successfully! Processed {$processed} out of {$total} products.");
			} elseif ($this->progress && ($this->progress['status'] ?? '') === 'failed') {
				session()->flash('error', 'Import failed: ' . ($this->progress['error'] ?? 'Unknown error'));
			} else {
				session()->flash('message', 'Import completed. Check status below.');
			}
		} catch (\Throwable $e) {
			\Log::error('Excel import dispatch failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
			session()->flash('error', 'Import failed: ' . $e->getMessage());
			$this->isImporting = false;
			// Update progress to failed
			if ($this->progressId) {
				Cache::put("import:{$this->progressId}", [
					'status' => 'failed',
					'error' => $e->getMessage(),
					'updated_at' => now()->toDateTimeString(),
				], now()->addHours(2));
				$this->progress = Cache::get("import:{$this->progressId}");
			}
		}
	}

	public function pollProgress()
	{
		if (!$this->progressId) return;
		$this->progress = Cache::get("import:{$this->progressId}");
		if ($this->progress && in_array($this->progress['status'] ?? '', ['completed', 'failed'])) {
			$this->isImporting = false;
		}
	}
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
	<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="mb-8">
			<h1 class="text-3xl font-bold text-gray-900 dark:text-white">Import Products from Excel</h1>
			<p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Required columns: Category, Tally name, HSN, Product Type, Size, Flavour, Package. Image1 will be used as thumbnail; Image2+ as additional images.</p>
		</div>

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

		<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6" @if($isImporting || $progressId) wire:poll.1000ms="pollProgress" @endif>
			<div class="grid grid-cols-1 gap-6">
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Seller</label>
					<select wire:model="seller_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
						<option value="">-- Choose Seller --</option>
						@foreach($sellers as $seller)
							<option value="{{ $seller->id }}">{{ $seller->company_name }}</option>
						@endforeach
					</select>
					@error('seller_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Excel File (.xlsx, .xls, .csv)</label>
					<input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="w-full text-gray-900 dark:text-white">
					@error('file') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
				</div>

				<div class="flex items-center gap-3">
					<button wire:click="import" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
						Import
					</button>
					<div wire:loading wire:target="import" class="text-sm text-gray-600 dark:text-gray-300">Importing...</div>
				</div>

				@if($progress)
					<div class="mt-4">
						<div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
							Status: <strong>{{ ucfirst($progress['status'] ?? 'queued') }}</strong>
							@if(isset($progress['percent'])) - {{ $progress['percent'] }}% @endif
							@if(isset($progress['processed'], $progress['total'])) ({{ $progress['processed'] }}/{{ $progress['total'] }}) @endif
						</div>
						<div class="w-full bg-gray-200 dark:bg-zinc-700 rounded">
							<div class="bg-blue-600 text-xs leading-none py-1 text-center text-white rounded" style="width: {{ $progress['percent'] ?? 0 }}%"></div>
						</div>
						@if(($progress['status'] ?? '') === 'failed' && ($progress['error'] ?? null))
							<div class="text-sm text-red-600 mt-2">Error: {{ $progress['error'] }}</div>
						@endif
					</div>
				@endif
			</div>
		</div>

		<div class="mt-8">
			<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Column Mapping Guide</h2>
			<ul class="list-disc pl-6 text-sm text-gray-700 dark:text-gray-300 space-y-1">
				<li><strong>Category</strong>: Mapped to existing or newly created category by name.</li>
				<li><strong>Tally name</strong>: Used as product name and description.</li>
				<li><strong>HSN</strong>: Saved on product.</li>
				<li><strong>Product Type</strong>: Saved on product.</li>
				<li><strong>Size</strong>, <strong>Flavour</strong>, <strong>Package</strong>: Created as required variants; each row forms a combination.</li>
				<li><strong>Image1</strong>: Product thumbnail. <strong>Image2..</strong>: Additional product images.</li>
			</ul>
		</div>
	</div>
</div>


