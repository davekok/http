<?php

declare(strict_types=1);

namespace davekok\http\wiring;

use davekok\parser\RulesBagFactory;
use davekok\wiring\NoSuchParameterWiringException;
use davekok\wiring\NoSuchRunnableWiringException;
use davekok\wiring\NoSuchServiceWiringException;
use davekok\wiring\NoSuchWireableWiringException;
use davekok\wiring\Runnable;
use davekok\wiring\Wireable;
use davekok\wiring\Wiring;
use davekok\wiring\WiringException;
use davekok\wiring\Wirings;
use ReflectionClass;

class HttpWiring implements Wiring
{
    private readonly HttpMountsWireable $mountsWireable;
    private readonly HttpFactoryWireable $factoryWireable;
    private readonly HttpFactory $factory;
    private readonly Wirings $wirings;

    private string|null $mainUrl;
    private string|null $redirectUrl;
    private string $server;

    public function setWirings(Wirings $wirings): void
    {
        $this->wirings = $wirings;
    }

    public function infoParameters(): array
    {
        return [
            "main-url"     => "The http URL on which to listen for connections, example 'https://0.0.0.0:443'.",
            "redirect-url" => "The http URL on which to listen for connections and redirect to main url, example 'http://0.0.0.0:80'.",
            "server"       => "Set the name of the server.",
        ];
    }

    public function setParameter(string $key, string|int|float|bool|array|null $value): void
    {
        match ($key) {
            "main-url"     => $this->mainUrl     = $value,
            "redirect-url" => $this->redirectUrl = $value,
            "server"       => $this->server      = $value,
            default        => throw new NoSuchParameterWiringException($key),
        };
    }

    public function getParameter(string $key): string|int|float|bool|array|null
    {
        return match ($key) {
            "main-url"     => $this->mainUrl     ??= null,
            "redirect-url" => $this->redirectUrl ??= null,
            "server"       => $this->server      ??= "https://github.com/davekok/http",
            default        => throw new NoSuchParameterWiringException($key),
        };
    }

    public function listRunnables(): array
    {
        return [];
    }

    public function helpRunnable(string $runnable): string
    {
        throw new NoSuchRunnableWiringException($runnable);
    }

    public function runnable(string $runnable, array $args): Runnable
    {
        throw new NoSuchRunnableWiringException($runnable);
    }

    public function prewire(): void
    {
        $this->factoryWireable = new HttpFactoryWireable($this);
        new HttpSchemeWireable($this->factoryWireable, $this->wirings->get("davekok", "kernel")->wireable("schemes"));
    }

    public function wireable(string $wireable): Wireable
    {
        return match ($wireable) {
            "mounts"  => $this->mountsWireable ??= new HttpMountsWireable(),
            default   => throw new NoSuchWireableWiringException($wireable),
        };
    }

    public function wire(): void
    {
        if (isset($this->mainUrl)) {
            $urlFactory = $this->wirings->get("kernel")->service("url-factory");
            $urlFactory->createUrl($this->mainUrl)
                ->bind($urlFactory->createUrl("activity:"))
                ->listen();
        }
    }

    public function service(string $service): object
    {
        return match ($service) {
            "factory" => $this->factoryWireable->wire(),
            default   => throw new NoSuchServiceWiringException($service),
        };
    }
}
