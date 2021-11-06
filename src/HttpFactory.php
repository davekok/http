<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\Rules;
use davekok\lalr1\RulesFactory;
use davekok\stream\Activity;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class HttpFactory
{
    private Rules $rules;

    public function __construct(private LoggerInterface $log = new NullLogger(), RulesFactory $rulesFactory = new RulesFactory()) {
        $this->rules = $rulesFactory->createRules(new ReflectionClass(HttpRules::class));
    }

    public function createReader(Activity $activity): HttpReader
    {
        $parser = new Parser($this->rules, $this->log);
        $rules  = new HttpRules($parser, $activity);
        $parser->setRulesObject($rules);
        return new HttpReader($activity, $parser, $rules);
    }

    public function createWriter(Activity $activity): HttpWriter
    {
        return new HttpWriter($activity);
    }
}
