<?php

/**
 * Global Helper Functions
 *
 * This file contains utility functions that are available throughout the application.
 * These helpers provide convenient access to common operations like domain resolution
 * and URL generation for the URL shortener functionality.
 */

use App\Models\Domain;
use App\Models\Link;

if (! function_exists('current_domain')) {
    /**
     * Get the current domain based on the request.
     *
     * Resolves the current domain by matching the request's protocol and host
     * against the domains configured in the database. This is used throughout
     * the application to determine which domain is serving the current request.
     *
     * @return Domain|null The matching domain or null if not found
     */
    function current_domain(): ?Domain
    {
        $request = request();

        return Domain::where('protocol', $request->getScheme())
            ->where('host', $request->httpHost())
            ->first();
    }
}

if (! function_exists('get_short_url')) {
    /**
     * Generate the full short URL for a link.
     *
     * Constructs the complete shortened URL by intelligently selecting the appropriate domain:
     * 1. Uses the provided domain if it's associated with the link
     * 2. Falls back to the current domain if the link is associated with it
     * 3. Uses the first available domain associated with the link
     *
     * The function ensures that only domains actually associated with the link are used,
     * maintaining proper access control and domain restrictions.
     *
     * @param  Link  $link  The link to generate the URL for
     * @param  Domain|null  $domain  The preferred domain to use
     * @return string|null The complete short URL or null if no suitable domain found
     */
    function get_short_url(Link $link, ?Domain $domain = null): ?string
    {
        // Check if provided domain is valid and associated with the link
        if (! (isset($domain) && $link->availableDomains()->where('domains.id', $domain->id)->exists())) {
            // If the domain is not provided or the link is not associated with the provided domain,
            $currentDomain = current_domain();
            if ($currentDomain && $link->availableDomains()->where('domains.id', $currentDomain->id)->exists()) {
                // If the link is associated with the current domain, use the current domain
                $domain = $currentDomain;
            } else {
                // Otherwise, use the first domain associated with the link
                $domain = $link->availableDomains()->first();
            }
        }

        if (! $domain) {
            return null;
        }

        // Generate the route URL for the link redirect
        $path = route('link.redirect', ['short_path' => $link->short_path], absolute: false);

        return "{$domain->protocol->value}://$domain->host$path";
    }
}
