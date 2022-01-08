<?php

declare(strict_types=1);

namespace davekok\http\wiring;

use davekok\http\HttpUrlFactory;
use davekok\kernel\wiring\Schemes;
use davekok\wiring\Wireable;
use davekok\wiring\Wirings;

class HttpSchemeWireable implements Wireable
{
    private readonly HttpUrlFactory $httpUrlFactory;

    public function __construct(
        private readonly HttpFactoryWireable $httpFactoryWireable,
        private readonly Schemes $schemes,
    ) {
        $this->schemes
            ->set("http", $this)
            ->set("https", $this);
    }

    public function wire(): HttpUrlFactory
    {
        return $this->httpUrlFactory ??= new HttpUrlFactory(
            httpFactory: $this->httpFactoryWireable->wire(),
            urlFactory:  $this->schemes->wire(),
        );
    }
}
