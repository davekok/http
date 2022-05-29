<?php

declare(strict_types=1);

namespace davekok\http;

interface StreamConnector
{
    public function connect(string $address, float|null $timeout = null): Stream;
}
