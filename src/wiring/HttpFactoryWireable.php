<?php

declare(strict_types=1);

namespace davekok\http\wiring;

use davekok\http\HttpFactory;
use davekok\http\HttpRules;
use davekok\parser\RulesBagFactory;
use davekok\wiring\Wireable;
use ReflectionClass;

class HttpFactoryWireable implements Wireable
{
    private readonly HttpFactory $factory;

    public function __construct(
        private readonly HttpWiring $wiring,
    ) {}

    public function wire(): HttpFactory
    {
        return $this->factory ??= new HttpFactory(
            rulesBag: (new RulesBagFactory)->createRulesBag(new ReflectionClass(HttpRules::class)),
            mounts:   $this->wiring->wireable("mounts")->wire(),
            server:   $this->wiring->getParameter("server"),
        );
    }
}
