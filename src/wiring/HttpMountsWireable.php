<?php

declare(strict_types=1);

namespace davekok\http\wiring;

use davekok\wiring\Configurable;
use davekok\wiring\Wireable;
use davekok\wiring\WiringException;

class HttpMountsWireable implements Configurable
{
    private readonly HttpMounts $httpMounts;
    private array $mounts = [];

    public function count(): int
    {
        return count($this->mounts);
    }

    public function set(string $mount, Wireable $wireable): static
    {
        $this->mounts[$mount] = $wireable;
        return $this;
    }

    public function get(string $mount): Wireable
    {
        return $this->mounts[$mount] ?? throw new WiringException("Mount not found: $mount");
    }

    public function all(): array
    {
        return $this->mounts;
    }

    public function wire(): HttpMounts
    {
        return $this->httpMounts ??= new HttpMounts($this->wireMounts());
    }

    private function wireMounts(): array
    {
        if ($this->count() === 0) {
            if (isset($this->mainUrl)) {
                throw new WiringException("The main-url is configured but no mounts are configured.");
            }
            return;
        }

        if (!isset($this->mainUrl)) {
            throw new WiringException("Mounts are configured but no main-url is configured.");
        }

        // wire the mounts
        $mounts = [];
        foreach ($this->mounts as $mount => $wireable) {
            $requestHandler = $wireable->wire();
            $requestHandler instanceof HttpRequestHandler ?: throw new WiringException("Expected a http request handler.");
            $mounts[$mount] = $requestHandler;
        }

        // sort mounts by longest first then alphabetical
        uksort($mounts, fn(string $mount1, string $mount2) => strlen($mount2) <=> strlen($mount1) ?: $mount1 <=> $mount2)

        return $mounts;
    }
}
