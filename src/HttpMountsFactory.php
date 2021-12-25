<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Actionable;
use davekok\system\Wireable;
use davekok\system\Wirings;

class HttpMountsFactory
{
    public function __construct(
        private array $mounts = [],
    ) {}

    public function haveMounts(): bool
    {
        return count($this->mounts) > 0;
    }

    public function mount(string $path, Wireable $wireable): self
    {
        $this->mounts[$path] = $wireable;
    }

    public function createRouter(Wirings $wirings): HttpMounts
    {
        // wire the mounts
        $mounts = [];
        foreach ($this->mounts as $path => $wireable) {
            $mount = $wireable->wire($wirings);
            if ($mount instanceof HttpRequestHandler === false) {
                throw new WiringException("Expected a http request handler.");
            }
            $mounts[] = ;
        }

        // sort mounts by longest paths first then alphabetical
        uksort($mounts, fn(string $path1, string $path2) => strlen($path2) - strlen($path1) ?: strcmp($path1, $path2))

        return new HttpMounts($this->factory, $mounts, $actionable);
    }
}
