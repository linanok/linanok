<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PharData;

/**
 * Download MaxMind GeoLite2 Country Database Command
 *
 * This command downloads the latest MaxMind GeoLite2-Country database
 * using the MaxMind license key and stores it in the storage/maxmind directory.
 */
class DownloadMaxMindDatabase extends Command
{
    protected $signature = 'maxmind:download';

    protected $description = 'Download the latest MaxMind GeoLite2-Country database';

    public function handle(): int
    {
        $licenseKey = config('services.maxmind.license_key');

        if (empty($licenseKey)) {
            $this->error('MaxMind license key is not configured. Please set MAXMIND_LICENSE_KEY in your .env file.');

            return 1;
        }

        $storageDir = storage_path('maxmind');
        $databasePath = $storageDir.'/GeoLite2-Country.mmdb';

        $this->info('Starting MaxMind GeoLite2-Country database download...');

        try {
            if (! File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }

            $tempFile = $this->downloadDatabase($licenseKey);
            $this->extractAndInstallDatabase($tempFile, $databasePath);

            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }

            $this->info('✓ MaxMind GeoLite2-Country database updated successfully!');
            $this->info("Database location: {$databasePath}");

            if (File::exists($databasePath)) {
                $size = File::size($databasePath);
                $this->info('Database size: '.$this->formatBytes($size));
            }

            Log::info('MaxMind GeoLite2-Country database updated successfully', [
                'database_path' => $databasePath,
                'file_size' => File::exists($databasePath) ? File::size($databasePath) : 0,
            ]);

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to download MaxMind database: '.$e->getMessage());
            Log::error('MaxMind database download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    private function downloadDatabase(string $licenseKey): string
    {
        $downloadUrl = config('services.maxmind.download_url');
        $edition = config('services.maxmind.database_edition');
        $suffix = config('services.maxmind.database_suffix');

        $tempFile = storage_path('maxmind/temp_'.time().'.tar.gz');

        $this->info('Downloading database from MaxMind...');

        $response = Http::timeout(300)
            ->withOptions(['verify' => true])
            ->get($downloadUrl, [
                'edition_id' => $edition,
                'license_key' => $licenseKey,
                'suffix' => $suffix,
            ]);

        if (! $response->successful()) {
            throw new Exception("Failed to download database. HTTP Status: {$response->status()}. Response: {$response->body()}");
        }

        $contentType = $response->header('Content-Type');
        if (! str_contains($contentType, 'application/gzip') && ! str_contains($contentType, 'application/x-gzip')) {
            $body = $response->body();
            if (str_contains($body, 'Invalid license key') || str_contains($body, 'error')) {
                throw new Exception("MaxMind API error: {$body}");
            }
        }

        File::put($tempFile, $response->body());

        if (! File::exists($tempFile) || File::size($tempFile) === 0) {
            throw new Exception('Downloaded file is empty or could not be saved');
        }

        return $tempFile;
    }

    private function extractAndInstallDatabase(string $tempFile, string $databasePath): void
    {
        $this->info('Extracting database file...');

        $tempDir = storage_path('maxmind/temp_extract_'.time());

        try {
            File::makeDirectory($tempDir, 0755, true);

            $phar = new PharData($tempFile);
            $phar->extractTo($tempDir);

            $subDirs = File::directories($tempDir);
            if (empty($subDirs)) {
                throw new Exception('Unexpected archive structure: no subdirectories found.');
            }

            $expectedFile = $subDirs[0].DIRECTORY_SEPARATOR.'GeoLite2-Country.mmdb';
            if (! File::exists($expectedFile)) {
                throw new Exception('Could not locate GeoLite2-Country.mmdb in the archive.');
            }

            File::move($expectedFile, $databasePath);

        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
