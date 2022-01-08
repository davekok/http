<?php

declare(strict_types=1);

namespace davekok\http;

use Countable;

class HttpMounts implements Countable
{
    public function __construct(
        private readonly array $mounts,
    ) {}

    public function count(): int
    {
        return count($this->mounts);
    }

    public function find(string $path): HttpRequestHandler
    {
        foreach ($this->mounts as $mount => $handler) {
            if (str_starts_with($path, $mount)) {
                return $handler;
            }
        }
        throw new HttpNotFoundException();
    }
}
