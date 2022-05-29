<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;

interface Stream
{
    public function read(): Generator;
    public function write(iterable $chunks): void;
}
