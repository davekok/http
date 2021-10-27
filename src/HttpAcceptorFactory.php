<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\Rules;
use davekok\lalr1\RulesFactory;
use davekok\stream\Acceptor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class HttpAcceptorFactory
{
    public function __construct(
        private RulesFactory $rulesFactory = new RulesFactory(),
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function createAcceptor(): Acceptor
    {
        return new HttpAcceptor($this->rulesFactory->createRules(new ReflectionClass(HttpRules::class)), $this->logger);
    }
}
