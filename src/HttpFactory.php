<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Actionable;
use davekok\parser\Parser;
use davekok\parser\RulesBag;

class HttpFactory
{
    public function __construct(
        private readonly RulesBag $rulesBag,
        private readonly HttpMounts $mounts,
        private readonly string $server,
    ) {}

    public function createController(HttpRequest $request): HttpController
    {
        return new HttpController($this->server, $request);
    }

    public function createRouter(Actionable $actionable, HttpUrl $url): HttpRouter
    {
        return new HttpRouter($this->mounts, new ActiveHttpActionable($this, $actionable, $url));
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
