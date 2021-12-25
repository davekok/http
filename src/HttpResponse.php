<?php

declare(strict_types=1);

namespace davekok\http;

class HttpResponse extends HttpMessage
{
    public function __construct(
        public readonly HttpStatus $status,
        float $protocolVersion,
        array $headers,
        string|Writer|null $body,
    )
    {
        parent::__construct($protocolVersion, $headers, $body);
    }
}
