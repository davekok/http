<?php

declare(strict_types=1);

namespace davekok\http;

class HttpResponse extends HttpMessage
{
    public function __construct(
        public readonly HttpStatus $status = HttpStatus::OK,
        float|null $protocolVersion        = null,
        array $headers                     = [],
        string|null $body                  = null,
    )
    {
        parent::__construct($protocolVersion, $headers, $body);
    }
}
