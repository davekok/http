<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\RulesBag;
use davekok\lalr1\RulesBagFactory;
use davekok\stream\Readable;
use davekok\stream\Writable;
use ReflectionClass;

class HttpComponent
{
    public readonly RulesBag $rulesBag;

    public function __construct(RulesBagFactory $rulesBagFactory = new RulesBagFactory())
    {
        $this->rulesBag = $rulesBagFactory->createRulesBag(new ReflectionClass(HttpRules::class));
    }

    public function read(Actionable $actionable, callable $andThen): void
    {
        $actionable instanceof Readable ?: throw new InvalidArgumentException("Expected an readable actionable.");
        $actionable->read($this->createReader($actionable), $andTen);
    }

    public function write(Actionable $actionable, HttpMessage $message): void
    {
        $actionable instanceof Writable ?: throw new InvalidArgumentException("Expected an writable actionable.");
        $actionable->write($this->createWriter($message));
    }

    public function createReader(Actionable $actionable): HttpReader
    {
        return new HttpReader(new Parser($this->rulesBag, new HttpRules($actionable)));
    }

    public function createWriter(HttpMessage $message): HttpWriter
    {
        return new HttpWriter($message);
    }
}
