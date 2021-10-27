<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\Rules;
use davekok\stream\Acceptor;
use davekok\stream\Socket;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpAcceptor implements Acceptor
{
    public function __construct(
        private Rules $rules,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function accept(Socket $socket): void
    {
        new HttpRules($this->logger, new Parser($this->rules, $this->logger), $socket);
    }
}
