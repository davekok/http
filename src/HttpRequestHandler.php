<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\ParserException;
use davekok\stream\ReaderException;

/**
 * HTTP servers should implement this interface to receive requests.
 */
interface HttpRequestHandler
{
    public function handleRequest(HttpRequest|ParserException|ReaderException $request): void;
}
