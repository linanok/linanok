<?php

namespace App\Observers;

use App\Models\Link;
use Illuminate\Support\Str;

/**
 * Link Observer
 *
 * Handles link model events to automatically generate unique short paths.
 * This observer ensures that every link has a unique short_path that can be
 * used in URLs, either based on a custom slug or randomly generated.
 * It also prevents conflicts with reserved routes and public paths.
 *
 * @see \App\Models\Link
 */
class LinkObserver
{
    /**
     * Reserved path prefixes that should not be used as short paths.
     * These correspond to common web application routes and assets.
     */
    private const PUBLIC_PATH_PREFIXES = [
        'admin',
        'filament',
        'livewire',
        'storage',
        'css',
        'js',
    ];

    /**
     * Reserved public files that should not be used as short paths.
     * These correspond to common web server files and assets.
     */
    private const PUBLIC_PATHS = [
        '.htaccess',
        'favicon.ico',
        'frankenphp-worker.php',
        'index.php',
        'linanok.svg',
        'logo.svg',
        'robots.txt',
    ];

    /**
     * Handle the Link "creating" event.
     *
     * Automatically generates a unique short_path for the link based on:
     * 1. The provided slug (if available) - ensures uniqueness by adding random suffix if needed
     * 2. A randomly generated string (if no slug provided)
     *
     * The generated short_path is guaranteed to be unique and not conflict with
     * reserved routes or existing links in the database.
     *
     * @param  Link  $link  The link being created
     */
    public function creating(Link $link): void
    {
        if (isset($link->slug)) {
            $link->short_path = $this->generateUniqueShortPath($link->slug);

            return;
        }

        // Generate a default unique slug if no slug is provided
        $link->short_path = $this->generateUniqueShortPath();
    }

    /**
     * Generate a unique short path for the link.
     *
     * If a base slug is provided, it first tries to use it as-is. If that's taken,
     * it appends a random 6-character string. If no base slug is provided,
     * it generates a completely random 6-character string.
     *
     * The method ensures the generated short path:
     * - Is unique in the database
     * - Does not conflict with reserved routes
     * - Does not conflict with public paths
     * - Does not start with a forward slash (to avoid absolute path conflicts)
     *
     * @param  string|null  $candidateShortPath  The desired short path base
     * @return string A unique short path that doesn't exist in the database
     */
    private function generateUniqueShortPath(?string $candidateShortPath = null): string
    {
        if ($candidateShortPath) {
            // If the candidate path conflicts with reserved routes, ignore it
            if ($this->isShortPathInReservedRoutes($candidateShortPath)) {
                $candidateShortPath = null;
            }

            // Reject paths starting with forward slash to avoid absolute path conflicts
            if (Str::startsWith($candidateShortPath, '/')) {
                $candidateShortPath = null;
            }
        }

        if ($candidateShortPath) {
            // First try the original short path candidate
            if (! $this->isShortPathTaken($candidateShortPath)) {
                return $candidateShortPath;
            }

            // If taken, append random suffix until we find a unique one
            do {
                $candidateShortPath = $candidateShortPath.Str::random(6);
                $exists = $this->isShortPathTaken($candidateShortPath);
            } while ($exists);

            return $candidateShortPath;
        }

        // Generate random short path if no base slug provided
        do {
            $candidateShortPath = Str::random(6);

            // Skip if it conflicts with reserved routes
            if ($this->isShortPathInReservedRoutes($candidateShortPath)) {
                continue;
            }

            $exists = $this->isShortPathTaken($candidateShortPath);
            if (! $exists) {
                break;
            }
        } while (true);

        return $candidateShortPath;
    }

    /**
     * Check if a short path conflicts with reserved routes or public paths.
     *
     * This method prevents conflicts with:
     * - Exact matches with public files (favicon.ico, robots.txt, etc.)
     * - Paths starting with reserved prefixes (admin/, css/, etc.)
     * - Exact matches with reserved prefixes (admin, css, etc.)
     *
     * @param  string  $shortPath  The short path to check
     * @return bool True if the path conflicts with reserved routes, false otherwise
     */
    private function isShortPathInReservedRoutes(string $shortPath): bool
    {
        $shortPathLower = Str::lower($shortPath);

        // Check against exact public file matches
        if (in_array($shortPathLower, self::PUBLIC_PATHS, true)) {
            return true;
        }

        // Check if the path starts with any reserved prefix
        foreach (self::PUBLIC_PATH_PREFIXES as $prefix) {
            if (Str::startsWith($shortPathLower, $prefix.'/')) {
                return true;
            }

            // Also consider exact match with prefix (like 'admin' or 'css')
            if ($shortPathLower === $prefix) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a short path is already taken by another link in the database.
     *
     * @param  string  $shortPath  The short path to check
     * @return bool True if the path is already taken, false otherwise
     */
    private function isShortPathTaken(string $shortPath): bool
    {
        return Link::where('short_path', $shortPath)->exists();
    }
}
