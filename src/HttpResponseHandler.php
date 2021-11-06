<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\ParserException;
use davekok\stream\ReaderException;

/**
 * HTTP clients should implement this interface to receive responses.
 */
interface HttpResponseHandler
{
    public function handleResponse(HttpResponse|ParserException|ReaderException $response): void;
}
