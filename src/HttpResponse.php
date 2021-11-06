<?php

declare(strict_types=1);

namespace davekok\http;

class HttpResponse extends HttpMessage
{
    public readonly HttpStatus $status;

    public function __construct(HttpStatus $status, float $protocolVersion, array $headers = [], string|null $body = null)
    {
        parent::__construct($protocolVersion, $headers, $body);
        $this->status = $status;
    }
}
