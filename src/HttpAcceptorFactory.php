<?php

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\Rules;
use davekok\lalr1\RulesFactory;
use davekok\stream\Acceptor;
use ReflectionClass;

class HttpAcceptorFactory
{
    public function __construct(
        private RulesFactory $rulesFactory = new RulesFactory()
    ) {}

    public function createAcceptor(): Acceptor
    {
        return new HttpAcceptor($this->rulesFactory->createRules(new ReflectionClass(HttpRules::class)));
    }
}
