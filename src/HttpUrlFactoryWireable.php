<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\parser\RulesBagFactory;
use davekok\system\NoSuchParameterWiringException;
use davekok\system\NoSuchServiceWiringException;
use davekok\system\NoSuchSetupServiceWiringException;
use davekok\system\Runnable;
use davekok\system\WiringException;
use davekok\system\Wireable;
use davekok\system\Wirings;
use ReflectionClass;

class HttpUrlFactoryWireable implements Wireable
{
    private readonly HttpUrlFactory httpUrlFactory;

    private function wire(Wirings $wirings): HttpUrlFactory
    {
        return $this->httpUrlFactory ??= $this->createHttpUrlFactory($wirings);
    }

    private function createHttpUrlFactory(Wirings $wirings): HttpUrlFactory
    {
        return new HttpUrlFactory($this->factory)
    }
}
