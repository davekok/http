<?php

declare(strict_types=1);

namespace davekok\http;

class HttpResponse extends HttpMessage
{
    public readonly HttpStatus $status;

    public function __construct(
        HttpStatus $status          = HttpStatus::OK,
        float|null $protocolVersion = null,
        array $headers              = [],
        string|null $body           = null,
    )
    {
        parent::__construct($protocolVersion, $headers, $body);
        $this->status = $status;
    }
}
