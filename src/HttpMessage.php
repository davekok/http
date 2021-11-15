<?php

declare(strict_types=1);

namespace davekok\http;

abstract class HttpMessage
{
    public function __construct(
        public readonly float|null  $protocolVersion = null,
        public readonly array       $headers         = [],
        public readonly string|null $body            = null,
    ) {}
}
