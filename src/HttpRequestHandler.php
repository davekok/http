<?php

declare(strict_types=1);

namespace davekok\http;

interface HttpRequestHandler
{
    public function handleHttpRequest(HttpRequest $request, HttpResponseFactory $responseFactory): HttpResponse;
}
