<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CleanOrphanImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean-orphan {--dry-run : Only show which files would be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unnecessary images or files from public/storage that are not referenced in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning database for referenced files...");

        // Keywords in column names to look for direct paths
        $imageColumnKeywords = ['image', 'photo', 'logo', 'certificate', 'document', 'path', 'cheque', 'signature', 'avatar', 'file', 'pic', 'icon', 'thumbnail', 'cover'];
        
        // Keywords in column names that might contain HTML snippets with images
        $htmlColumnKeywords = ['content', 'description', 'body', 'text', 'html'];

        $tablesArray = Schema::getTables();
        $tables = array_map(function ($table) {
            return is_array($table) ? ($table['name'] ?? null) : ($table->name ?? null);
        }, $tablesArray);
        $tables = array_filter($tables);
        
        $usedFiles = [];

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            
            $targetPathColumns = [];
            $targetHtmlColumns = [];

            foreach ($columns as $column) {
                $lowerCol = strtolower($column);
                $isPath = false;
                
                foreach ($imageColumnKeywords as $keyword) {
                    if (str_contains($lowerCol, $keyword)) {
                        $targetPathColumns[] = $column;
                        $isPath = true;
                        break;
                    }
                }
                
                if (!$isPath) {
                    foreach ($htmlColumnKeywords as $keyword) {
                        if (str_contains($lowerCol, $keyword)) {
                            $targetHtmlColumns[] = $column;
                            break;
                        }
                    }
                }
            }

            $allTargetColumns = array_merge($targetPathColumns, $targetHtmlColumns);

            if (!empty($allTargetColumns)) {
                $query = DB::table($table)->select($allTargetColumns);
                
                $query->where(function($q) use ($allTargetColumns) {
                    foreach ($allTargetColumns as $col) {
                        $q->orWhere(function($subQ) use ($col) {
                            $subQ->whereNotNull($col)->where($col, '!=', '');
                        });
                    }
                });

                $results = $query->get();

                foreach ($results as $row) {
                    // Process direct path columns
                    foreach ($targetPathColumns as $col) {
                        if (!empty($row->{$col})) {
                            $value = $row->{$col};
                            
                            if (is_string($value)) {
                                $decoded = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    foreach ($decoded as $item) {
                                        if (is_string($item)) {
                                            $usedFiles[] = $this->normalizePath($item);
                                        }
                                    }
                                } else {
                                    $usedFiles[] = $this->normalizePath($value);
                                }
                            }
                        }
                    }
                    
                    // Process HTML columns for image sources
                    foreach ($targetHtmlColumns as $col) {
                        if (!empty($row->{$col}) && is_string($row->{$col})) {
                            // Extract possible src attributes from HTML
                            preg_match_all('/src="([^"]+)"/i', $row->{$col}, $matches);
                            if (!empty($matches[1])) {
                                foreach ($matches[1] as $src) {
                                    // only care if it points to storage
                                    if (str_contains($src, '/storage/')) {
                                        $usedFiles[] = $this->normalizePath($src);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $usedFiles = array_filter(array_unique($usedFiles));
        
        $this->info("Found " . count($usedFiles) . " unique file references in the database.");

        $this->info("Scanning storage directory...");
        
        $disk = Storage::disk('public');
        $allFiles = $disk->allFiles('');
        
        // Filter out hidden files or specific system directories
        $filteredFiles = array_filter($allFiles, function ($file) {
            $ignoreSystem = str_starts_with($file, '.') || str_starts_with($file, 'livewire-tmp');
            return !$ignoreSystem;
        });

        $this->info("Found " . count($filteredFiles) . " valid files in storage.");

        $deletedCount = 0;
        $sizeSaved = 0;

        foreach ($filteredFiles as $file) {
            if (!in_array($file, $usedFiles)) {
                $size = $disk->size($file);
                if ($this->option('dry-run')) {
                    $this->line("Would delete: " . $file . " (" . round($size / 1024, 2) . " KB)");
                } else {
                    $disk->delete($file);
                    $this->line("Deleted: " . $file);
                }
                $deletedCount++;
                $sizeSaved += $size;
            }
        }

        $mbSaved = round($sizeSaved / 1024 / 1024, 2);
        if ($this->option('dry-run')) {
            $this->info("Dry run complete. Would delete {$deletedCount} files. (Would free {$mbSaved} MB)");
        } else {
            $this->info("Cleanup complete. Deleted {$deletedCount} files. (Freed {$mbSaved} MB)");
        }
    }

    private function normalizePath($path)
    {
        // Strip out base URLs or /storage/ prefix to match standard Storage::disk('public')->allFiles format
        if (str_starts_with($path, 'http')) {
            $parsed = parse_url($path, PHP_URL_PATH);
            if ($parsed) {
                // Remove base path including /storage/
                $path = preg_replace('/^.*\/storage\//i', '', $parsed);
            }
        } else {
             $path = preg_replace('/^.*\/storage\//i', '', $path);
        }
        
        return ltrim($path, '/');
    }
}
