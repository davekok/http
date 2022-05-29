<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;

class SimpleStream implements Stream
{
    public function __construct(
        public readonly mixed $stream,
    ) {}

    public function write(iterable $chunks): void
    {
        foreach ($chunks as $chunk) {
            fwrite($this->stream, $chunk);
        }
    }

    public function read(): Generator
    {
        while ($chunk = fread($this->stream, 8192)) {
            yield $chunk;
        }
    }
}
