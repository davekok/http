<?php

namespace davekok\http;

use davekok\lalr1\Rules;
use davekok\lalr1\Parser;
use davekok\stream\Acceptor;

class HttpAcceptor implements Acceptor
{
    public function __construct(private Rules $rules) {}

    public function accept(Connection $connection): void
    {
        new HttpRules(new Parser($this->rules), $connection);
    }
}
