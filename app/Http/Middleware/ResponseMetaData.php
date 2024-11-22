<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class ResponseMetaData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Proceed with the request and get the response
        $response = $next($request);

        // Determine the version based on the prefix
        $version = '1.0'; // Default to version 1.0
        if ($request->is('v2/*')) {
            $version = '2.0';
        }

        // Check if the response is a JSON response
        if ($response instanceof JsonResponse) {
            // Get the original data from the response
            $data = $response->getData(true);

            // Add the version information
            $data['version'] = $version;
            $data['timestamp'] = now();

            // Set the modified data back into the response
            $response->setData($data);
        }

        return $response;
    }
}
