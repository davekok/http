<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Acceptor;
use davekok\kernel\Actionable;
use davekok\kernel\Activity;
use davekok\kernel\Url;

class PassiveHttpActionable implements Actionable, Acceptor
{
    public function __construct(
        private readonly HttpFactory $httpFactory,
        private readonly Actionable  $actionable,
        private readonly HttpUrl     $url,
    ) {}

    public function actionable(): Actionable
    {
        return $this->actionable;
    }

    public function activity(): Activity
    {
        return $this->actionable->activity();
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function listen(): self
    {
        $this->actionable->listen($this);
        return $this;
    }

    public function accept(Actionable $actionable): void
    {
        $this->httpFactory->createRouter($actionable, $this->url);
    }
}
