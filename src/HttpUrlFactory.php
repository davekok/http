<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\UrlFactory;
use davekok\kernel\KernelException;

class HttpUrlFactory implements UrlFactory
{
    public function __construct(
        private readonly HttpFactory $httpFactory,
        private readonly SocketUrlFactory $socketUrlFactory,
    ) {}

    public function createUrl(string $url): Url
    {
        $parts = parse_url($url);

        isset($parts["scheme"]) === true
        && ($parts["scheme"] === "http" || $parts["scheme"] === "https")
        && isset($parts["host"]) === true
        ?: throw new KernelException("Not a http url: $url");

        return new HttpUrl(
            httpFactory: $this->httpFactory,
            socketUrl:   $this->socketUrlFactory->createUrl(scheme: "tcp", host: $parts["host"], port: $parts["port"]),
            scheme:      $parts["scheme"],
            username:    $parts["user"],
            password:    $parts["pass"],
            host:        $parts["host"],
            port:        $parts["port"],
            path:        $parts["path"],
            query:       $parts["query"],
            fragment:    $parts["fragment"],
        );
    }
}
