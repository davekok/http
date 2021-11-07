<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\stream\Url;

class HttpRequest extends HttpMessage
{
    public readonly string $method;
    public readonly Url    $url;

    public function __construct(
        string      $method,
        Url         $url,
        float|null  $protocolVersion = null,
        array       $headers         = [],
        string|null $body            = null,
    ) {
        parent::__construct($protocolVersion, $headers, $body);
        $this->method = $method;
        $this->url    = $url;
    }
}
