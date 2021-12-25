<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Url;

class HttpRequest extends HttpMessage
{
    public function __construct(
        public readonly string $method,
        public readonly Url    $url,
        float                  $protocolVersion,
        array                  $headers,
        string|null            $body,
    ) {
        parent::__construct($protocolVersion, $headers, $body);
    }
}
