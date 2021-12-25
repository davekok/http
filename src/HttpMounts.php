<?php

declare(strict_types=1);

namespace davekok\http;

class HttpMounts
{
    public function __construct(
        private readonly array $mounts,
    ) {}

    private function findMount(string $path): HttpRequestHandler
    {
        foreach ($this->mounts as $mount => $handler) {
            if (str_starts_with($path, $mount)) {
                return $handler;
            }
        }
        throw new HttpNotFoundException();
    }
}
