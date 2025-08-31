<?php

namespace App\Services;

use App\Jobs\SaveVisitJob;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

/**
 * Visit Service
 *
 * Handles the redirection logic for shortened links and tracks visit analytics.
 * This service is responsible for:
 * - Dispatching visit tracking jobs
 * - Managing query parameter forwarding
 * - Adding referrer information
 * - Redirecting users to the original URL
 *
 * @see \App\Jobs\SaveVisitJob
 * @see \App\Models\Link
 */
class VisitService
{
    /**
     * Redirect to the original URL and track the visit.
     *
     * This method handles the core functionality of the URL shortener:
     * 1. Dispatches a job to save visit analytics asynchronously
     * 2. Processes query parameters based on link configuration
     * 3. Adds referrer information if enabled
     * 4. Redirects the user to the original URL
     *
     * @param  Link  $link  The link being visited
     * @return Redirector|RedirectResponse The redirect response
     */
    public static function redirectToOriginalUrl(Link $link): Redirector|RedirectResponse
    {
        $request = request();

        // Dispatch job to save visit analytics asynchronously
        $currentDomain = current_domain();
        SaveVisitJob::dispatch($link->id, [
            'headers' => $request->headers,
            'ip' => $request->ip(),
            'domain_id' => $currentDomain?->id,
        ]);

        // Build query parameters based on link configuration
        $queryParameters = collect();

        // Add referrer parameter if enabled
        if ($link->send_ref_query_parameter) {
            $queryParameters->put('ref', $request->httpHost());
        }

        // Forward original query parameters if enabled
        if ($link->forward_query_parameters) {
            $queryParameters = $queryParameters->merge($request->query);
        }

        // Build the final URL with or without query parameters
        $url = Request::create($link->original_url);
        if (! $queryParameters->isEmpty()) {
            $url = $url->fullUrlWithQuery($queryParameters->toArray());
        } else {
            $url = $url->fullUrl();
        }

        return redirect($url);
    }
}
