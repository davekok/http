<?php

declare(strict_types=1);

namespace davekok\http;

class HttpContainer
{
    public function __construct(
        public readonly HttpFilter $filter,
    ) {}
}
