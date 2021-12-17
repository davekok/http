<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\parser\RulesBagFactory;
use ReflectionClass;

class HttpContainerFactory
{
    public function __construct(private readonly RulesBagFactory $rulesBagFactory = new RulesBagFactory) {}

    public function set(string $key, mixed $value): self
    {
        throw new HttpException("Option not supported: $key");
    }

    public function get(string $key): mixed
    {
        throw new HttpException("Option not supported: $key");
    }

    public function info(): array
    {
        return [];
    }

    public function createContainer(): HttpContainer
    {
        return new HttpContainer(
            filter: new HttpFilter($this->rulesBagFactory->createRulesBag(new ReflectionClass(HttpRules::class)))
        );
    }
}
