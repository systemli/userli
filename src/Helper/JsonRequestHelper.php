<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;

final readonly class JsonRequestHelper
{
    /**
     * Returns true if the request is likely to expect a JSON response.
     * This is determined by checking if the request path starts with '/api/',
     * if the 'Accept' header includes 'application/json', if the 'Content-Type'
     * header is 'application/json', or if the request format is set to 'json'.
     */
    public static function wantsJson(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
            || in_array('application/json', $request->getAcceptableContentTypes())
            || $request->headers->get('Content-Type') === 'application/json'
            || $request->getRequestFormat() === 'json';
    }
}
