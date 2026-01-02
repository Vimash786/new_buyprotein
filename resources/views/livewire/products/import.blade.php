<?php

use App\Exports\ProductsTemplateExport;
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

	// Column mapping properties
	public $productType = 'variant'; // 'variant' or 'non_variant'
	public $variantTypes = ['Size', 'Flavour', 'Package'];
	public $newVariantType = '';
	public $columns = [];
	public $showColumnConfig = true;

	// Predefined column options
	public $availableColumns = [
		// Common columns
		['key' => 'category', 'label' => 'Category', 'group' => 'basic'],
		['key' => 'name', 'label' => 'Product Name', 'group' => 'basic'],
		['key' => 'tally_name', 'label' => 'Tally Name', 'group' => 'basic'],
		['key' => 'description', 'label' => 'Description', 'group' => 'basic'],
		['key' => 'hsn', 'label' => 'HSN Code', 'group' => 'basic'],
		['key' => 'product_type', 'label' => 'Product Type', 'group' => 'basic'],
		['key' => 'sku', 'label' => 'SKU', 'group' => 'basic'],
		['key' => 'asin', 'label' => 'ASIN/Product ID', 'group' => 'identifier'],
		['key' => 'ean', 'label' => 'EAN', 'group' => 'identifier'],
		// Pricing columns
		['key' => 'gym_owner_price', 'label' => 'Gym Owner Price', 'group' => 'pricing'],
		['key' => 'regular_user_price', 'label' => 'Regular User Price', 'group' => 'pricing'],
		['key' => 'shop_owner_price', 'label' => 'Shop Owner Price', 'group' => 'pricing'],
		['key' => 'gym_owner_discount', 'label' => 'Gym Owner Discount %', 'group' => 'pricing'],
		['key' => 'regular_user_discount', 'label' => 'Regular User Discount %', 'group' => 'pricing'],
		['key' => 'shop_owner_discount', 'label' => 'Shop Owner Discount %', 'group' => 'pricing'],
		// Stock columns
		['key' => 'stock_quantity', 'label' => 'Stock Quantity', 'group' => 'stock'],
		['key' => 'weight', 'label' => 'Weight', 'group' => 'stock'],
		// Image columns
		['key' => 'image1', 'label' => 'Image1', 'group' => 'images'],
		['key' => 'image2', 'label' => 'Image2', 'group' => 'images'],
		['key' => 'image3', 'label' => 'Image3', 'group' => 'images'],
		['key' => 'image4', 'label' => 'Image4', 'group' => 'images'],
		['key' => 'image5', 'label' => 'Image5', 'group' => 'images'],
	];

	public function mount()
	{
		$this->initializeColumns();
	}

	public function initializeColumns()
	{
		if ($this->productType === 'variant') {
			$this->columns = ProductsTemplateExport::getVariantColumns($this->variantTypes);
		} else {
			$this->columns = ProductsTemplateExport::getNonVariantColumns();
		}
	}

	public function updatedProductType()
	{
		$this->initializeColumns();
	}

	public function addVariantType()
	{
		if (!empty($this->newVariantType) && !in_array($this->newVariantType, $this->variantTypes)) {
			$this->variantTypes[] = $this->newVariantType;
			$this->newVariantType = '';
			$this->initializeColumns();
		}
	}

	public function removeVariantType($index)
	{
		if (count($this->variantTypes) > 1) {
			unset($this->variantTypes[$index]);
			$this->variantTypes = array_values($this->variantTypes);
			$this->initializeColumns();
		}
	}

	public function addColumn($key)
	{
		$column = collect($this->availableColumns)->firstWhere('key', $key);
		if ($column && !collect($this->columns)->contains('key', $key)) {
			$this->columns[] = [
				'key' => $column['key'],
				'label' => $column['label'],
				'required' => false,
				'example' => '',
				'description' => '',
			];
		}
	}

	public function removeColumn($index)
	{
		unset($this->columns[$index]);
		$this->columns = array_values($this->columns);
	}

	public function toggleRequired($index)
	{
		$this->columns[$index]['required'] = !$this->columns[$index]['required'];
	}

	public function moveColumnUp($index)
	{
		if ($index > 0) {
			$temp = $this->columns[$index - 1];
			$this->columns[$index - 1] = $this->columns[$index];
			$this->columns[$index] = $temp;
		}
	}

	public function moveColumnDown($index)
	{
		if ($index < count($this->columns) - 1) {
			$temp = $this->columns[$index + 1];
			$this->columns[$index + 1] = $this->columns[$index];
			$this->columns[$index] = $temp;
		}
	}

	public function downloadTemplate()
	{
		$filename = $this->productType === 'variant' 
			? 'products_variant_template.xlsx' 
			: 'products_simple_template.xlsx';

		return Excel::download(
			new ProductsTemplateExport(
				$this->columns,
				$this->productType === 'variant',
				$this->variantTypes
			),
			$filename
		);
	}

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

		// Increase execution time for large imports
		set_time_limit(600);
		ini_set('max_execution_time', '600');

		try {
			$stored = $this->file->storeAs('private/imports', Str::uuid()->toString() . '.' . $this->file->getClientOriginalExtension(), 'local');
			$this->progressId = Str::uuid()->toString();
			
			Cache::put("import:{$this->progressId}", [
				'status' => 'running',
				'total' => 0,
				'processed' => 0,
				'percent' => 0,
				'updated_at' => now()->toDateTimeString(),
			], now()->addHours(2));
			$this->progress = Cache::get("import:{$this->progressId}");
			
			// Store column mapping in cache for the import job
			Cache::put("import_config:{$this->progressId}", [
				'product_type' => $this->productType,
				'variant_types' => $this->variantTypes,
				'columns' => $this->columns,
			], now()->addHours(2));
			
			ImportProductsFromExcel::dispatchSync($stored, (int)$this->seller_id, $this->progressId, 'local');
			
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
	<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="mb-8">
			<h1 class="text-3xl font-bold text-gray-900 dark:text-white">Import Products from Excel</h1>
			<p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Configure column mapping, download template, and import your products.</p>
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

		<!-- Step 1: Product Type Selection -->
		<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6">
			<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
				<span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">1</span>
				Select Product Type
			</h2>
			
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div 
					wire:click="$set('productType', 'variant')"
					class="cursor-pointer border-2 rounded-lg p-4 transition-all {{ $productType === 'variant' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400' }}"
				>
					<div class="flex items-start">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 {{ $productType === 'variant' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
							</svg>
						</div>
						<div class="ml-4">
							<h3 class="text-lg font-medium text-gray-900 dark:text-white">Variant Products</h3>
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
								Products with multiple variants (Size, Flavour, Package, etc.). Each row represents one variant combination.
							</p>
							<div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
								<strong>Example:</strong> Whey Protein with 1kg/2kg sizes, Chocolate/Vanilla flavors
							</div>
						</div>
					</div>
				</div>

				<div 
					wire:click="$set('productType', 'non_variant')"
					class="cursor-pointer border-2 rounded-lg p-4 transition-all {{ $productType === 'non_variant' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400' }}"
				>
					<div class="flex items-start">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 {{ $productType === 'non_variant' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
							</svg>
						</div>
						<div class="ml-4">
							<h3 class="text-lg font-medium text-gray-900 dark:text-white">Simple Products</h3>
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
								Products without variants. Each row is a separate product with single price and stock.
							</p>
							<div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
								<strong>Example:</strong> Shaker Bottle, Gym Bag, Single supplement
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Step 2: Variant Types Configuration (for variant products) -->
		@if($productType === 'variant')
		<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6">
			<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
				<span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">2</span>
				Configure Variant Types
			</h2>
			<p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
				Define the variant attributes for your products. Each variant type will become a column in your Excel template.
			</p>

			<div class="flex flex-wrap gap-2 mb-4">
				@foreach($variantTypes as $index => $type)
					<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
						{{ $type }}
						@if(count($variantTypes) > 1)
							<button wire:click="removeVariantType({{ $index }})" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400">
								<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
								</svg>
							</button>
						@endif
					</span>
				@endforeach
			</div>

			<div class="flex gap-2">
				<input 
					type="text" 
					wire:model="newVariantType"
					wire:keydown.enter="addVariantType"
					placeholder="Add variant type (e.g., Color, Weight)"
					class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white text-sm"
				>
				<button 
					wire:click="addVariantType"
					class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
				>
					Add
				</button>
			</div>

			<div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
				<div class="flex">
					<svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
					</svg>
					<div class="text-sm text-amber-800 dark:text-amber-200">
						<strong>How Variants Work:</strong> Products with the same ASIN/Product ID will be grouped together. Each row represents one variant combination. For example, a protein product might have multiple rows for different size-flavour-package combinations.
					</div>
				</div>
			</div>
		</div>
		@endif

		<!-- Step 3: Column Mapping Configuration -->
		<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
					<span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">{{ $productType === 'variant' ? '3' : '2' }}</span>
					Column Mapping
				</h2>
				<button 
					wire:click="$toggle('showColumnConfig')"
					class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
				>
					{{ $showColumnConfig ? 'Hide Details' : 'Show Details' }}
				</button>
			</div>

			@if($showColumnConfig)
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
					<thead class="bg-gray-50 dark:bg-zinc-800">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Column Name</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Excel Header</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Required</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Example</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
						</tr>
					</thead>
					<tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
						@foreach($columns as $index => $column)
							<tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
								<td class="px-4 py-3">
									<div class="flex items-center space-x-1">
										<button wire:click="moveColumnUp({{ $index }})" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" {{ $index === 0 ? 'disabled' : '' }}>
											<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
										</button>
										<button wire:click="moveColumnDown({{ $index }})" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" {{ $index === count($columns) - 1 ? 'disabled' : '' }}>
											<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
										</button>
									</div>
								</td>
								<td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $column['key'] }}</td>
								<td class="px-4 py-3">
									<input 
										type="text" 
										wire:model.blur="columns.{{ $index }}.label"
										class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
									>
								</td>
								<td class="px-4 py-3">
									<button 
										wire:click="toggleRequired({{ $index }})"
										class="px-2 py-1 text-xs rounded {{ $column['required'] ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}"
									>
										{{ $column['required'] ? 'Required' : 'Optional' }}
									</button>
								</td>
								<td class="px-4 py-3">
									<input 
										type="text" 
										wire:model.blur="columns.{{ $index }}.example"
										placeholder="Sample value"
										class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
									>
								</td>
								<td class="px-4 py-3">
									<button 
										wire:click="removeColumn({{ $index }})"
										class="text-red-600 hover:text-red-800 dark:text-red-400"
									>
										<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
										</svg>
									</button>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<!-- Add More Columns -->
			<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
				<h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add More Columns:</h4>
				<div class="flex flex-wrap gap-2">
					@foreach($availableColumns as $availableCol)
						@if(!collect($columns)->contains('key', $availableCol['key']))
							<button 
								wire:click="addColumn('{{ $availableCol['key'] }}')"
								class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-full"
							>
								+ {{ $availableCol['label'] }}
							</button>
						@endif
					@endforeach
				</div>
			</div>
			@endif

			<!-- Download Template Button -->
			<div class="mt-6 flex items-center gap-4">
				<button 
					wire:click="downloadTemplate"
					class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium"
				>
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
					</svg>
					Download Excel Template
				</button>
				<span class="text-sm text-gray-500 dark:text-gray-400">
					Template configured for {{ $productType === 'variant' ? 'variant' : 'simple' }} products
					@if($productType === 'variant')
						with {{ implode(', ', $variantTypes) }} variants
					@endif
				</span>
			</div>
		</div>

		<!-- Step 4: Import Section -->
		<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6" @if($isImporting || $progressId) wire:poll.1000ms="pollProgress" @endif>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
				<span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">{{ $productType === 'variant' ? '4' : '3' }}</span>
				Import Products
			</h2>

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
					<button wire:click="import" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
						<span wire:loading.remove wire:target="import">Import Products</span>
						<span wire:loading wire:target="import">Importing...</span>
					</button>
					<div wire:loading wire:target="file" class="text-sm text-gray-600 dark:text-gray-300">Uploading file...</div>
				</div>

				@if($progress)
					<div class="mt-4">
						<div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
							Status: <strong>{{ ucfirst($progress['status'] ?? 'queued') }}</strong>
							@if(isset($progress['percent'])) - {{ $progress['percent'] }}% @endif
							@if(isset($progress['processed'], $progress['total'])) ({{ $progress['processed'] }}/{{ $progress['total'] }}) @endif
						</div>
						<div class="w-full bg-gray-200 dark:bg-zinc-700 rounded">
							<div class="bg-blue-600 text-xs leading-none py-1 text-center text-white rounded transition-all duration-300" style="width: {{ $progress['percent'] ?? 0 }}%">
								{{ $progress['percent'] ?? 0 }}%
							</div>
						</div>
						@if(($progress['status'] ?? '') === 'failed' && ($progress['error'] ?? null))
							<div class="text-sm text-red-600 mt-2">Error: {{ $progress['error'] }}</div>
						@endif
					</div>
				@endif
			</div>
		</div>

		<!-- Guide Section -->
		<div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
			<!-- Variant Products Guide -->
			<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
				<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
					<svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
					</svg>
					Variant Products Guide
				</h3>
				<div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
					<div class="flex items-start">
						<span class="w-5 h-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">1</span>
						<p><strong>ASIN/Product ID:</strong> Use the same ID for all variants of a single product. This groups them together.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">2</span>
						<p><strong>Variant Columns:</strong> Each variant type (Size, Flavour, etc.) becomes a column. Fill in the specific value for each row.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">3</span>
						<p><strong>Pricing:</strong> Set prices at the variant level. Each combination can have different prices.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">4</span>
						<p><strong>Images:</strong> Image1 becomes the product thumbnail. Additional images are shared across variants.</p>
					</div>
				</div>
				<div class="mt-4 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
					<p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
						Example: 3 rows with same ASIN "WP001":<br>
						Row 1: WP001 | 1kg | Chocolate | Jar<br>
						Row 2: WP001 | 2kg | Vanilla | Pouch<br>
						Row 3: WP001 | 5kg | Strawberry | Bucket
					</p>
				</div>
			</div>

			<!-- Non-Variant Products Guide -->
			<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
				<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
					<svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
					</svg>
					Simple Products Guide
				</h3>
				<div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
					<div class="flex items-start">
						<span class="w-5 h-5 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">1</span>
						<p><strong>One Row Per Product:</strong> Each row represents a complete, standalone product.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">2</span>
						<p><strong>Direct Pricing:</strong> Set prices directly on the product. No variant combinations needed.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">3</span>
						<p><strong>Stock Quantity:</strong> Single stock count for the entire product.</p>
					</div>
					<div class="flex items-start">
						<span class="w-5 h-5 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs mr-2 mt-0.5">4</span>
						<p><strong>Images:</strong> Image1 is the thumbnail. Image2-5 are additional gallery images.</p>
					</div>
				</div>
				<div class="mt-4 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
					<p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
						Example:<br>
						Row 1: Protein | Shaker Bottle | 299 | 349 | 279 | 100<br>
						Row 2: Accessories | Gym Bag | 999 | 1199 | 899 | 50
					</p>
				</div>
			</div>
		</div>

		<!-- Column Reference -->
		<div class="mt-6 bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
			<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Column Reference</h3>
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
				<div class="space-y-2">
					<h4 class="font-medium text-gray-900 dark:text-white">Basic Information</h4>
					<ul class="text-gray-600 dark:text-gray-400 space-y-1">
						<li>• <strong>Category:</strong> Product category name</li>
						<li>• <strong>Name/Tally Name:</strong> Product display name</li>
						<li>• <strong>Description:</strong> Product description</li>
						<li>• <strong>HSN:</strong> HSN code for taxation</li>
						<li>• <strong>Product Type:</strong> Type classification</li>
					</ul>
				</div>
				<div class="space-y-2">
					<h4 class="font-medium text-gray-900 dark:text-white">Pricing</h4>
					<ul class="text-gray-600 dark:text-gray-400 space-y-1">
						<li>• <strong>Gym Owner Price:</strong> Price for gym owners</li>
						<li>• <strong>Regular User Price:</strong> Standard price</li>
						<li>• <strong>Shop Owner Price:</strong> Wholesale price</li>
						<li>• <strong>Discount %:</strong> Percentage discount per user type</li>
					</ul>
				</div>
				<div class="space-y-2">
					<h4 class="font-medium text-gray-900 dark:text-white">Stock & Media</h4>
					<ul class="text-gray-600 dark:text-gray-400 space-y-1">
						<li>• <strong>Stock Quantity:</strong> Available inventory</li>
						<li>• <strong>Weight:</strong> Product weight (e.g., 1kg)</li>
						<li>• <strong>SKU:</strong> Stock keeping unit code</li>
						<li>• <strong>Images:</strong> URLs to product images</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>


