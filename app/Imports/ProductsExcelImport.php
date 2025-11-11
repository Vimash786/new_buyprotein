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
	}

	/**
	 * Expected columns (case-insensitive, spaces/underscores tolerated):
	 * - category (or category_type)
	 * - asin
	 * - sku
	 * - tally name
	 * - ean
	 * - hsn
	 * - image1, image2, image3, image4, image5, image6, image7, image8
	 * - product_type
	 * - size
	 * - flavour
	 * - package
	 */
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

		// Group by ASIN if present, else by Tally Name to form products with variants
		$grouped = $normalized->groupBy(function ($row) {
			return $row->get('asin') ?: $row->get('tally_name') ?: Str::uuid()->toString();
		});

		$total = $grouped->count();
		$processed = 0;
		$this->setProgress('running', $total, $processed);

		Log::info('Products import started', ['total_groups' => $total]);

		foreach ($grouped as $asin => $items) {
			try {
				Log::info('Processing product group', [
					'asin_or_key' => $asin,
					'rows_in_group' => $items->count(),
					'current_index' => $processed + 1,
					'total' => $total,
				]);

				// Run each group atomically to avoid one failure blocking others
				DB::transaction(function () use ($asin, $items) {
				// Validate required product-level fields
					$first = $items->first();
					$productType = $first->get('product_type') ?? $first->get('producttype');
					$categoryName = $first->get('category') ?? $first->get('category_type');
					$tallyName = $first->get('tally_name') ?? $first->get('tallyname') ?? $first->get('name');
					$hsn = $first->get('hsn');

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
						'description' => $tallyName, // as requested: tally name contains description too
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

					// Prepare variants: Size, Flavour, Package (all required)
					$sizeVariant = ProductVariant::create([
						'product_id' => $product->id,
						'name' => 'Size',
						'display_name' => 'Size',
						'sort_order' => 0,
						'is_required' => true,
					]);
					$flavourVariant = ProductVariant::create([
						'product_id' => $product->id,
						'name' => 'Flavour',
						'display_name' => 'Flavour',
						'sort_order' => 1,
						'is_required' => true,
					]);
					$packageVariant = ProductVariant::create([
						'product_id' => $product->id,
						'name' => 'Package',
						'display_name' => 'Package',
						'sort_order' => 2,
						'is_required' => true,
					]);

					// Create options maps to avoid duplicates
					$sizeOptions = [];
					$flavourOptions = [];
					$packageOptions = [];

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
						$size = $row->get('size');
						$flavour = $row->get('flavour') ?? $row->get('flavor');
						$package = $row->get('package');
						$sku = $row->get('sku');

						// Required fields check
						if (empty($size) || empty($flavour) || empty($package)) {
							Log::warning('Skipping row; missing size/flavour/package', ['sku' => $sku, 'asin' => $asin]);
							continue;
						}

						$sizeOpt = $ensureOption($sizeVariant, (string)$size, $sizeOptions);
						$flavourOpt = $ensureOption($flavourVariant, (string)$flavour, $flavourOptions);
						$packageOpt = $ensureOption($packageVariant, (string)$package, $packageOptions);
						if (!$sizeOpt || !$flavourOpt || !$packageOpt) {
							continue;
						}

						ProductVariantCombination::create([
							'product_id' => $product->id,
							'variant_options' => [$sizeOpt->id, $flavourOpt->id, $packageOpt->id],
							'sku' => $sku ?: null,
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
							'is_active' => true,
						]);
					}

					// Handle images (download and attach)
					$firstRow = $items->first();
					$imageUrls = [];
					for ($i = 1; $i <= 10; $i++) {
						// Support both Image1 and Image-1 / Image 1 (normalized to image_1)
						$keysToCheck = ['image' . $i, 'image_' . $i];
						foreach ($keysToCheck as $col) {
							if ($firstRow->has($col) && !empty($firstRow->get($col))) {
								$imageUrls[] = $firstRow->get($col);
								break;
							}
						}
					}

					Log::info('Image URLs collected', ['asin_or_key' => $asin, 'count' => count($imageUrls)]);
					if (!empty($imageUrls)) {
						$this->downloadAndAttachImages($product, $imageUrls);
					}
				});

				Log::info('Product group processed', ['asin_or_key' => $asin]);
			} catch (\Throwable $e) {
				Log::error('Failed processing product group', [
					'asin_or_key' => $asin,
					'error' => $e->getMessage(),
				]);
				// continue with next group
			} finally {
				$processed++;
				$this->setProgress('running', $total, $processed);
			}
		}

		$this->setProgress('completed', $total, $processed);
	}

	/**
	 * Download images and attach to product. First image used as thumbnail.
	 *
	 * @param products $product
	 * @param array<int,string> $urls
	 */
	protected function downloadAndAttachImages(products $product, array $urls): void
	{
		// Download in parallel
		$responses = Http::timeout(20)
			->connectTimeout(10)
			->pool(function (Pool $pool) use ($urls) {
				return collect($urls)->map(function ($url) use ($pool) {
					return $pool->as((string)$url)
						->withOptions([
							// In case some sources have bad SSL. Adjust as needed.
							'verify' => false,
						])
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
				// Set product thumbnail
				$product->thumbnail_image = $filename;
				$product->save();
			} else {
				// Additional images
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


