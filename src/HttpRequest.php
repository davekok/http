<?php

declare(strict_types=1);

namespace davekok\http;

class HttpRequest extends HttpMessage
{
    public readonly string $method;
    public readonly string $path;

    public function __construct(string $method, string $path, float $protocolVersion, array $headers = [], string|null $body = null)
    {
        parent::__construct($protocolVersion, $headers, $body);
        $this->method = $method;
        $this->path   = $path;
    }
}
