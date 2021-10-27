<?php

declare(strict_types=1);

namespace davekok\http;

class HttpRequest
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly float $protocolVersion,
        public readonly array $headers
    ) {}

    public function __toString(): string {
        $request = "{$this->method} {$this->path} HTTP/{$this->protocolVersion}\n";
        foreach ($this->headers as $headerName => $headerValue) {
            $request .= "{$headerName}:{$headerValue}\n";
        }
        $request .= "\n";
        return $request;
    }
}
