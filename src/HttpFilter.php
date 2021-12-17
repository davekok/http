<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Actionable;
use davekok\kernel\Cryptoble;
use davekok\kernel\Readable;
use davekok\kernel\Writable;
use davekok\parser\Parser;
use davekok\parser\RulesBag;
use InvalidArgumentException;

class HttpFilter
{
    public function __construct(private readonly RulesBag $rulesBag) {}

    public function read(Actionable $actionable, callable $setter): void
    {
        $actionable instanceof Readable ?: throw new InvalidArgumentException("Expected an readable actionable.");
        $actionable->read(
            $this->createReader($actionable instanceof Cryptoble ? $actionable->isCryptoEnabled() : false),
            $setter
        );
    }

    public function write(Actionable $actionable, HttpMessage $message): void
    {
        $actionable instanceof Writable ?: throw new InvalidArgumentException("Expected an writable actionable.");
        $actionable->write($this->createWriter($message));
    }

    public function createReader(bool $isCryptoEnabled = false): HttpReader
    {
        return new HttpReader(new Parser($this->rulesBag, new HttpRules($isCryptoEnabled)));
    }

    public function createWriter(HttpMessage $message): HttpWriter
    {
        return new HttpWriter($message);
    }
}
