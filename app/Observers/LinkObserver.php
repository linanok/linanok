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
 *
 * @see \App\Models\Link
 */
class LinkObserver
{
    /**
     * Handle the Link "creating" event.
     *
     * Automatically generates a unique short_path for the link based on:
     * 1. The provided slug (if available) - ensures uniqueness by adding random suffix if needed
     * 2. A randomly generated string (if no slug provided)
     *
     * The generated short_path is guaranteed to be unique and not already taken
     * by existing links in the database.
     *
     * @param  Link  $link  The link being created
     */
    public function creating(Link $link): void
    {
        if (isset($link->slug)) {
            $link->short_path = self::generateUniqueShortPath($link->slug);

            return;
        }

        // Generate a default unique slug if no slug is provided
        $link->short_path = self::generateUniqueShortPath();
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
     * - Does not start or end with a slash, and contains no double slashes
     * - Uses only allowed characters: a-z, A-Z, 0-9, -, _, ., and /
     *
     * @param  string|null  $candidateShortPath  The desired short path base
     * @return string A unique short path that doesn't exist in the database
     */
    private static function generateUniqueShortPath(?string $candidateShortPath = null): string
    {
        if ($candidateShortPath) {
            // Reject invalid short paths (leading/trailing slash, double slashes, or invalid characters)
            if (! self::isShortPathValid($candidateShortPath)) {
                $candidateShortPath = null;
            }
        }

        if ($candidateShortPath) {
            // First try the original short path candidate
            if (! self::isShortPathTaken($candidateShortPath)) {
                return $candidateShortPath;
            }

            // If taken, append random suffix until we find a unique one
            do {
                $candidateShortPath = $candidateShortPath.'_'.Str::random(6);
                $exists = self::isShortPathTaken($candidateShortPath);
            } while ($exists);

            return $candidateShortPath;
        }

        // Generate random short path if no base slug provided
        do {
            $candidateShortPath = Str::random(6);
            $exists = self::isShortPathTaken($candidateShortPath);
        } while ($exists);

        return $candidateShortPath;
    }

    /**
     * Validate that the short path contains only allowed characters and structure.
     *
     * Rules:
     * - Only a-z, A-Z, 0-9, -, _, ., and /
     * - No leading or trailing slashes
     * - No double slashes
     */
    private static function isShortPathValid(string $shortPath): bool
    {
        // Reject if leading or trailing slash
        if (Str::startsWith($shortPath, '/') || Str::endsWith($shortPath, '/')) {
            return false;
        }

        // Reject if double slashes exist
        if (str_contains($shortPath, '//')) {
            return false;
        }

        // Must match allowed characters only (segments of letters, numbers, -, _, . separated by slashes)
        return preg_match('/^[a-zA-Z0-9\-_.\/]+$/', $shortPath) === 1;
    }

    /**
     * Check if a short path is already taken by another link in the database.
     *
     * @param  string  $shortPath  The short path to check
     * @return bool True if the path is already taken, false otherwise
     */
    private static function isShortPathTaken(string $shortPath): bool
    {
        return Link::where('short_path', $shortPath)->exists();
    }
}
