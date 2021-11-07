<?php

declare(strict_types=1);

namespace davekok\http;

abstract class HttpMessage
{
    public readonly float|null $protocolVersion;
    public readonly array       $headers;
    public readonly string|null $body;

    public function __construct(float|null $protocolVersion = null, array $headers = [], string|null $body = null)
    {
        $this->protocolVersion = $protocolVersion;
        $this->headers         = $headers;
        $this->body            = $body;
    }
}
