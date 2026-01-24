<?php

namespace App\Jobs;

use App\Imports\ProductsExcelImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsFromExcel implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $timeout = 0; // no hard limit, let queue worker manage timeouts
	public int $tries = 1;

	protected string $storedPath;
	protected string $disk;
	protected int $sellerId;
	protected string $progressId;

	/**
	 * @param string $storedPath path relative to the specified disk root (e.g., 'private/imports/uuid.xlsx')
	 * @param int $sellerId
	 * @param string $progressId unique id for cache progress key
	 * @param string $disk filesystem disk name (default 'local')
	 */
	public function __construct(string $storedPath, int $sellerId, string $progressId, string $disk = 'local')
	{
		$this->storedPath = $storedPath;
		$this->sellerId = $sellerId;
		$this->progressId = $progressId;
		$this->disk = $disk;
	}

	public function handle(): void
	{
		$fullPath = \Illuminate\Support\Facades\Storage::disk($this->disk)->path($this->storedPath);

		// Initialize progress
		Cache::put("import:{$this->progressId}", [
			'status' => 'queued',
			'total' => 0,
			'processed' => 0,
			'percent' => 0,
			'updated_at' => now()->toDateTimeString(),
		], now()->addHours(2));

		try {
			Log::info('Queued Excel import started', [
				'seller_id' => $this->sellerId,
				'progress_id' => $this->progressId,
				'path' => $fullPath,
			]);
			Excel::import(new ProductsExcelImport($this->sellerId, null, $this->progressId), $fullPath);
			Log::info('Queued Excel import finished', [
				'seller_id' => $this->sellerId,
				'progress_id' => $this->progressId,
			]);
		} catch (\Throwable $e) {
			Log::error('Queued Excel import failed', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'progress_id' => $this->progressId,
				'seller_id' => $this->sellerId,
			]);
			Cache::put("import:{$this->progressId}", [
				'status' => 'failed',
				'total' => 0,
				'processed' => 0,
				'percent' => 0,
				'error' => $e->getMessage(),
				'updated_at' => now()->toDateTimeString(),
			], now()->addHours(2));
			throw $e;
		}
	}
}


