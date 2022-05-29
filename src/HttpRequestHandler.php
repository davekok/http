<?php

declare(strict_types=1);

namespace davekok\http;

interface HttpRequestHandler
{
    public function handleRequest(HttpRequest $request): HttpResponse;
}
