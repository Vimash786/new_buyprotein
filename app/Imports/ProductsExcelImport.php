<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantCombination;
use App\Models\ProductVariantOption;
use App\Models\products;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsExcelImport implements ToCollection, WithHeadingRow
{
	protected int $sellerId;
	protected ?int $defaultCategoryId;
	protected ?string $progressKey = null;
	protected array $importConfig = [];

	/**
	 * @param int $sellerId Seller who owns the imported products
	 * @param int|null $defaultCategoryId Fallback category when not matched from Excel
	 * @param string|null $progressKey Cache key suffix for progress tracking
	 */
	public function __construct(int $sellerId, ?int $defaultCategoryId = null, ?string $progressKey = null)
	{
		$this->sellerId = $sellerId;
		$this->defaultCategoryId = $defaultCategoryId;
		$this->progressKey = $progressKey ? "import:{$progressKey}" : null;
		
		// Load import configuration from cache if available
		if ($progressKey) {
			$this->importConfig = Cache::get("import_config:{$progressKey}", [
				'product_type' => 'variant',
				'variant_types' => ['Size', 'Flavour', 'Package'],
				'columns' => [],
			]);
		}
	}

	/**
	 * Check if we're importing variant products
	 */
	protected function isVariantImport(): bool
	{
		return ($this->importConfig['product_type'] ?? 'variant') === 'variant';
	}

	/**
	 * Get configured variant types
	 */
	protected function getVariantTypes(): array
	{
		return $this->importConfig['variant_types'] ?? ['Size', 'Flavour', 'Package'];
	}

	/**
	 * Dynamic column mapping - get value from row with flexible key matching
	 */
	protected function getColumnValue(Collection $row, string $columnKey, $default = null)
	{
		// Direct key match
		if ($row->has($columnKey)) {
			$value = $row->get($columnKey);
			return $value !== null && $value !== '' ? $value : $default;
		}

		// Try snake_case version
		$snakeKey = Str::snake($columnKey);
		if ($row->has($snakeKey)) {
			$value = $row->get($snakeKey);
			return $value !== null && $value !== '' ? $value : $default;
		}

		// Try without underscores
		$noUnderscoreKey = str_replace('_', '', $snakeKey);
		if ($row->has($noUnderscoreKey)) {
			$value = $row->get($noUnderscoreKey);
			return $value !== null && $value !== '' ? $value : $default;
		}

		// Try common variations
		$variations = [
			'tally_name' => ['tallyname', 'tally_name', 'name', 'product_name', 'productname'],
			'category' => ['category', 'category_type', 'categorytype'],
			'product_type' => ['product_type', 'producttype', 'type'],
			'gym_owner_price' => ['gym_owner_price', 'gymownerprice', 'gym_price'],
			'regular_user_price' => ['regular_user_price', 'regularuserprice', 'regular_price', 'price'],
			'shop_owner_price' => ['shop_owner_price', 'shopownerprice', 'shop_price', 'wholesale_price'],
			'stock_quantity' => ['stock_quantity', 'stockquantity', 'stock', 'quantity'],
		];

		if (isset($variations[$columnKey])) {
			foreach ($variations[$columnKey] as $variation) {
				if ($row->has($variation)) {
					$value = $row->get($variation);
					return $value !== null && $value !== '' ? $value : $default;
				}
			}
		}

		return $default;
	}

	public function collection(Collection $rows)
	{
		if ($rows->isEmpty()) {
			return;
		}

		// Normalize headings to snake_case for easy access
		$normalized = $rows->map(function ($row) {
			$mapped = [];
			foreach ($row as $key => $value) {
				$k = Str::of($key)->lower()->replace([' ', '-'], '_')->toString();
				$mapped[$k] = is_string($value) ? trim($value) : $value;
			}
			return collect($mapped);
		});

		if ($this->isVariantImport()) {
			$this->importVariantProducts($normalized);
		} else {
			$this->importSimpleProducts($normalized);
		}
	}

	/**
	 * Import products with variants (grouped by ASIN or product identifier)
	 */
	protected function importVariantProducts(Collection $normalized): void
	{
		// Group by ASIN if present, else by Tally Name to form products with variants
		$grouped = $normalized->groupBy(function ($row) {
			return $this->getColumnValue($row, 'asin') 
				?: $this->getColumnValue($row, 'tally_name') 
				?: Str::uuid()->toString();
		});

		$total = $grouped->count();
		$processed = 0;
		$this->setProgress('running', $total, $processed);

		Log::info('Variant products import started', ['total_groups' => $total]);

		$variantTypes = $this->getVariantTypes();

		foreach ($grouped as $asin => $items) {
			try {
				Log::info('Processing product group', [
					'asin_or_key' => $asin,
					'rows_in_group' => $items->count(),
					'current_index' => $processed + 1,
					'total' => $total,
				]);

				DB::transaction(function () use ($asin, $items, $variantTypes) {
					$first = $items->first();
					
					$productType = $this->getColumnValue($first, 'product_type');
					$categoryName = $this->getColumnValue($first, 'category');
					$tallyName = $this->getColumnValue($first, 'tally_name');
					$hsn = $this->getColumnValue($first, 'hsn');
					$description = $this->getColumnValue($first, 'description', $tallyName);

					if (empty($categoryName) || empty($productType) || empty($tallyName)) {
						Log::warning('Skipping group; missing required product fields', ['asin' => $asin]);
						return;
					}

					// Find or create category
					$category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();
					if (!$category) {
						$category = Category::create([
							'name' => $categoryName,
							'slug' => Str::slug($categoryName),
							'description' => null,
							'image' => null,
							'is_active' => true,
							'sort_order' => 0,
						]);
					}

					// Create product
					$product = products::create([
						'seller_id' => $this->sellerId,
						'category_id' => $category->id,
						'sub_category_id' => null,
						'name' => $tallyName,
						'description' => $description,
						'gym_owner_price' => 0,
						'regular_user_price' => 0,
						'shop_owner_price' => 0,
						'gym_owner_discount' => 0,
						'regular_user_discount' => 0,
						'shop_owner_discount' => 0,
						'gym_owner_final_price' => 0,
						'regular_user_final_price' => 0,
						'shop_owner_final_price' => 0,
						'stock_quantity' => 0,
						'weight' => null,
						'status' => 'active',
						'section_category' => ['everyday_essential'],
						'has_variants' => true,
						'hsn' => $hsn,
						'product_type' => $productType,
					]);

					// Create variant types dynamically
					$variants = [];
					$optionsCaches = [];
					
					foreach ($variantTypes as $index => $variantType) {
						$variants[$variantType] = ProductVariant::create([
							'product_id' => $product->id,
							'name' => $variantType,
							'display_name' => $variantType,
							'sort_order' => $index,
							'is_required' => true,
						]);
						$optionsCaches[$variantType] = [];
					}

					$ensureOption = function (ProductVariant $variant, string $value, array &$cache) {
						$key = mb_strtolower(trim($value));
						if ($key === '') {
							return null;
						}
						if (isset($cache[$key])) {
							return $cache[$key];
						}
						$opt = ProductVariantOption::create([
							'product_variant_id' => $variant->id,
							'value' => $value,
							'stock_quantity' => 0,
							'sort_order' => count($cache),
							'is_active' => true,
						]);
						$cache[$key] = $opt;
						return $opt;
					};

					// Create combinations for each row
					foreach ($items as $row) {
						$variantOptionIds = [];
						$hasAllVariants = true;
						
						foreach ($variantTypes as $variantType) {
							$variantKey = strtolower(str_replace(' ', '_', $variantType));
							$value = $this->getColumnValue($row, $variantKey);
							
							if (empty($value)) {
								Log::warning('Skipping row; missing variant value', [
									'variant_type' => $variantType,
									'asin' => $asin
								]);
								$hasAllVariants = false;
								break;
							}
							
							$option = $ensureOption($variants[$variantType], (string)$value, $optionsCaches[$variantType]);
							if (!$option) {
								$hasAllVariants = false;
								break;
							}
							$variantOptionIds[] = $option->id;
						}

						if (!$hasAllVariants) {
							continue;
						}

						$sku = $this->getColumnValue($row, 'sku');
						$gymOwnerPrice = floatval($this->getColumnValue($row, 'gym_owner_price', 0));
						$regularUserPrice = floatval($this->getColumnValue($row, 'regular_user_price', 0));
						$shopOwnerPrice = floatval($this->getColumnValue($row, 'shop_owner_price', 0));
						$gymOwnerDiscount = floatval($this->getColumnValue($row, 'gym_owner_discount', 0));
						$regularUserDiscount = floatval($this->getColumnValue($row, 'regular_user_discount', 0));
						$shopOwnerDiscount = floatval($this->getColumnValue($row, 'shop_owner_discount', 0));
						$stockQuantity = intval($this->getColumnValue($row, 'stock_quantity', 0));

						// Calculate final prices
						$gymOwnerFinalPrice = $gymOwnerPrice * (1 - $gymOwnerDiscount / 100);
						$regularUserFinalPrice = $regularUserPrice * (1 - $regularUserDiscount / 100);
						$shopOwnerFinalPrice = $shopOwnerPrice * (1 - $shopOwnerDiscount / 100);

						ProductVariantCombination::create([
							'product_id' => $product->id,
							'variant_options' => $variantOptionIds,
							'sku' => $sku ?: null,
							'gym_owner_price' => $gymOwnerPrice,
							'regular_user_price' => $regularUserPrice,
							'shop_owner_price' => $shopOwnerPrice,
							'gym_owner_discount' => $gymOwnerDiscount,
							'regular_user_discount' => $regularUserDiscount,
							'shop_owner_discount' => $shopOwnerDiscount,
							'gym_owner_final_price' => $gymOwnerFinalPrice,
							'regular_user_final_price' => $regularUserFinalPrice,
							'shop_owner_final_price' => $shopOwnerFinalPrice,
							'stock_quantity' => $stockQuantity,
							'is_active' => true,
						]);
					}

					// Handle images
					$this->processImages($product, $items->first());
				});

				Log::info('Product group processed', ['asin_or_key' => $asin]);
			} catch (\Throwable $e) {
				Log::error('Failed processing product group', [
					'asin_or_key' => $asin,
					'error' => $e->getMessage(),
				]);
			} finally {
				$processed++;
				$this->setProgress('running', $total, $processed);
			}
		}

		$this->setProgress('completed', $total, $processed);
	}

	/**
	 * Import simple products (one row per product, no variants)
	 */
	protected function importSimpleProducts(Collection $normalized): void
	{
		$total = $normalized->count();
		$processed = 0;
		$this->setProgress('running', $total, $processed);

		Log::info('Simple products import started', ['total_products' => $total]);

		foreach ($normalized as $row) {
			try {
				DB::transaction(function () use ($row) {
					$categoryName = $this->getColumnValue($row, 'category');
					$productName = $this->getColumnValue($row, 'name') ?: $this->getColumnValue($row, 'tally_name');
					$productType = $this->getColumnValue($row, 'product_type');
					$description = $this->getColumnValue($row, 'description', $productName);
					$hsn = $this->getColumnValue($row, 'hsn');
					$sku = $this->getColumnValue($row, 'sku');
					$weight = $this->getColumnValue($row, 'weight');

					if (empty($categoryName) || empty($productName)) {
						Log::warning('Skipping row; missing required fields', [
							'category' => $categoryName,
							'name' => $productName
						]);
						return;
					}

					// Find or create category
					$category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();
					if (!$category) {
						$category = Category::create([
							'name' => $categoryName,
							'slug' => Str::slug($categoryName),
							'description' => null,
							'image' => null,
							'is_active' => true,
							'sort_order' => 0,
						]);
					}

					// Get pricing
					$gymOwnerPrice = floatval($this->getColumnValue($row, 'gym_owner_price', 0));
					$regularUserPrice = floatval($this->getColumnValue($row, 'regular_user_price', 0));
					$shopOwnerPrice = floatval($this->getColumnValue($row, 'shop_owner_price', 0));
					$gymOwnerDiscount = floatval($this->getColumnValue($row, 'gym_owner_discount', 0));
					$regularUserDiscount = floatval($this->getColumnValue($row, 'regular_user_discount', 0));
					$shopOwnerDiscount = floatval($this->getColumnValue($row, 'shop_owner_discount', 0));
					$stockQuantity = intval($this->getColumnValue($row, 'stock_quantity', 0));

					// Calculate final prices
					$gymOwnerFinalPrice = $gymOwnerPrice * (1 - $gymOwnerDiscount / 100);
					$regularUserFinalPrice = $regularUserPrice * (1 - $regularUserDiscount / 100);
					$shopOwnerFinalPrice = $shopOwnerPrice * (1 - $shopOwnerDiscount / 100);

					// Create product
					$product = products::create([
						'seller_id' => $this->sellerId,
						'category_id' => $category->id,
						'sub_category_id' => null,
						'name' => $productName,
						'description' => $description,
						'gym_owner_price' => $gymOwnerPrice,
						'regular_user_price' => $regularUserPrice,
						'shop_owner_price' => $shopOwnerPrice,
						'gym_owner_discount' => $gymOwnerDiscount,
						'regular_user_discount' => $regularUserDiscount,
						'shop_owner_discount' => $shopOwnerDiscount,
						'gym_owner_final_price' => $gymOwnerFinalPrice,
						'regular_user_final_price' => $regularUserFinalPrice,
						'shop_owner_final_price' => $shopOwnerFinalPrice,
						'stock_quantity' => $stockQuantity,
						'weight' => $weight,
						'status' => 'active',
						'section_category' => ['everyday_essential'],
						'has_variants' => false,
						'hsn' => $hsn,
						'product_type' => $productType,
					]);

					// Handle images
					$this->processImages($product, $row);

					Log::info('Simple product created', ['product_id' => $product->id, 'name' => $productName]);
				});
			} catch (\Throwable $e) {
				Log::error('Failed processing simple product', [
					'error' => $e->getMessage(),
				]);
			} finally {
				$processed++;
				$this->setProgress('running', $total, $processed);
			}
		}

		$this->setProgress('completed', $total, $processed);
	}

	/**
	 * Process and attach images to product
	 */
	protected function processImages(products $product, Collection $row): void
	{
		$imageUrls = [];
		
		// Log all keys in row for debugging
		Log::info('Row keys for image processing', ['keys' => $row->keys()->toArray()]);
		
		for ($i = 1; $i <= 10; $i++) {
			// Check various possible column name formats
			$keysToCheck = [
				'image' . $i,
				'image_' . $i,
				'image' . $i . '_(thumbnail)',
				'image_' . $i . '_(thumbnail)',
				'image_' . $i . '_thumbnail',
				'image' . $i . '_thumbnail',
			];
			
			$found = false;
			foreach ($keysToCheck as $col) {
				if ($row->has($col) && !empty($row->get($col))) {
					$url = trim($row->get($col));
					if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
						$imageUrls[] = $url;
						Log::info('Found image URL', ['column' => $col, 'url' => $url]);
						$found = true;
						break;
					}
				}
			}
			
			// Also try to find by partial match if not found
			if (!$found) {
				foreach ($row->keys() as $key) {
					if (preg_match('/^image[_]?' . $i . '/i', $key)) {
						$url = trim($row->get($key));
						if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
							$imageUrls[] = $url;
							Log::info('Found image URL by regex', ['column' => $key, 'url' => $url]);
							break;
						}
					}
				}
			}
		}

		Log::info('Image URLs collected', ['product_id' => $product->id, 'count' => count($imageUrls), 'urls' => $imageUrls]);
		if (!empty($imageUrls)) {
			$this->downloadAndAttachImages($product, $imageUrls);
		}
	}

	/**
	 * Download images and attach to product. First image used as thumbnail.
	 */
	protected function downloadAndAttachImages(products $product, array $urls): void
	{
		$responses = Http::timeout(20)
			->connectTimeout(10)
			->pool(function (Pool $pool) use ($urls) {
				return collect($urls)->map(function ($url) use ($pool) {
					return $pool->as((string)$url)
						->withOptions(['verify' => false])
						->get((string)$url);
				})->all();
			});

		$index = 0;
		foreach ($urls as $url) {
			$response = $responses[(string)$url] ?? null;
			if (!$response || !$response->ok()) {
				Log::warning('Image download failed', [
					'url' => $url,
					'status' => $response ? $response->status() : null,
				]);
				continue;
			}

			$extension = $this->guessExtensionFromResponse($response->header('content-type'));
			if (!$extension) {
				$parsedPath = parse_url($url, PHP_URL_PATH);
				if (is_string($parsedPath)) {
					$extFromUrl = pathinfo($parsedPath, PATHINFO_EXTENSION);
					$extension = $extFromUrl ?: 'jpg';
				} else {
					$extension = 'jpg';
				}
			}
			$filename = 'products/' . ($index === 0 ? 'thumbnails/' : 'images/') . Str::uuid()->toString() . '.' . $extension;

			Storage::disk('public')->put($filename, $response->body());

			if ($index === 0) {
				$product->thumbnail_image = $filename;
				$product->save();
			} else {
				ProductImage::create([
					'product_id' => $product->id,
					'image_path' => $filename,
					'sort_order' => $index,
					'is_primary' => false,
					'file_size' => Storage::disk('public')->size($filename) ?: null,
					'image_type' => 'product',
				]);
			}

			$index++;
		}
	}

	protected function guessExtensionFromResponse(?string $contentType): ?string
	{
		return match ($contentType) {
			'image/jpeg', 'image/jpg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			'image/avif' => 'avif',
			default => null
		};
	}

	protected function setProgress(string $status, int $total, int $processed): void
	{
		if (!$this->progressKey) {
			return;
		}
		Cache::put($this->progressKey, [
			'status' => $status,
			'total' => $total,
			'processed' => $processed,
			'percent' => $total > 0 ? round(($processed / max(1, $total)) * 100) : 0,
			'updated_at' => now()->toDateTimeString(),
		], now()->addHours(2));
	}
}


