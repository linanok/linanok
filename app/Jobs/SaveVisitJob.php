<?php

namespace App\Jobs;

use App\Models\Visit;
use donatj\UserAgent\UserAgentParser;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use MaxMind\Db\Reader;

/**
 * Save Visit Job
 *
 * Asynchronously processes and saves visit analytics data.
 * This job is dispatched whenever a shortened link is accessed to avoid
 * blocking the redirect response with analytics processing.
 *
 * The job performs:
 * - User agent parsing to extract browser and platform information
 * - IP geolocation lookup using MaxMind GeoLite2 database (if available)
 * - Database storage of visit analytics
 *
 * @see \App\Models\Visit
 * @see \App\Services\VisitService
 */
class SaveVisitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int  $linkId  The ID of the link being visited
     * @param  array  $request  Request data containing headers, IP, and domain info
     */
    public function __construct(
        private readonly int $linkId,
        private readonly array $request
    ) {}

    /**
     * Execute the job.
     *
     * Processes the visit data and saves it to the database within a transaction.
     * Parses user agent information and performs IP geolocation lookup if MaxMind database is available.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Parse user agent to extract browser and platform information
            $userAgentParser = new UserAgentParser;
            $parsedUserAgent = $userAgentParser->parse($this->request['headers']->get('User-Agent'));

            // Attempt IP geolocation lookup using MaxMind GeoLite2 database
            $country = $this->getCountryFromIp($this->request['ip']);

            // Create the visit record with parsed analytics data
            Visit::create([
                'link_id' => $this->linkId,
                'ip' => $this->request['ip'],
                'browser' => $parsedUserAgent->browser(),
                'country' => $country,
                'platform' => $parsedUserAgent->platform(),
                'domain_id' => $this->request['domain_id'],
            ]);
        });
    }

    /**
     * Get country code from IP address using MaxMind database.
     * Returns null if database is not available or lookup fails.
     */
    private function getCountryFromIp(string $ip): ?string
    {
        $databasePath = storage_path('maxmind/GeoLite2-Country.mmdb');

        // Check if MaxMind database file exists
        if (! File::exists($databasePath)) {
            Log::warning('MaxMind GeoLite2-Country database not found', [
                'database_path' => $databasePath,
                'ip' => $ip,
            ]);

            return null;
        }

        try {
            // Initialize MaxMind reader for IP geolocation
            $maxMindReader = new Reader($databasePath);
            $result = $maxMindReader->get($ip);

            return $result['country']['iso_code'] ?? null;

        } catch (Exception $e) {
            Log::warning('Failed to lookup country for IP address', [
                'ip' => $ip,
                'error' => $e->getMessage(),
                'database_path' => $databasePath,
            ]);

            return null;
        }
    }
}
