<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\parser\RulesBagFactory;
use davekok\system\NoSuchParameterWiringException;
use davekok\system\NoSuchServiceWiringException;
use davekok\system\NoSuchSetupServiceWiringException;
use davekok\system\Runnable;
use davekok\system\WiringException;
use davekok\system\WiringInterface;
use davekok\system\Wirings;
use ReflectionClass;

class Wiring implements WiringInterface
{
    private readonly HttpRouterWireable $routerWireable;

    private string|null $httpUrl  = null;
    private string|null $httpsUrl = null;
    private string      $server   = "https://github.com/davekok/http";

    public function infoParameters(): array
    {
        return [
            "http-url"  => "The http URL on which to listen for HTTP connections, no default, example 'http://0.0.0.0:80'.",
            "https-url" => "The http URL on which to listen for HTTPS connections, no default, example 'https://0.0.0.0:443'.",
            "server"    => "Set the name of the server, defaults to 'https://github.com/davekok/http'.",
        ];
    }

    public function setParameter(string $key, string|int|float|bool|null $value): void
    {
        match ($key) {
            "http-url"  => $this->httpUrl  = $value,
            "https-url" => $this->httpsUrl = $value,
            "server"    => $this->server   = $value,
            default     => throw new NoSuchParameterWiringException($key),
        };
    }

    public function getParameter(string $key): string|int|float|bool|null
    {
        return match ($key) {
            "http-url"  => $this->httpUrl,
            "https-url" => $this->httpsUrl,
            "server"    => $this->server,
            default     => throw new NoSuchParameterWiringException($key),
        };
    }

    public function prewire(Wirings $wirings): void
    {
        $wireable = new HttpUrlFactoryWireable();
        $wirings->get("kernel")
            ->setupService("schemes")
            ->setScheme("http", $wireable)
            ->setScheme("https", $wireable);
    }

    public function wireService(string $key): object
    {
        return match ($key) {
            "router" => $this->routerWireable ??= new HttpRouterWireable(),
            default  => throw new NoSuchWireServiceWiringException($key),
        };
    }

    public function wire(Wirings $wirings): Runnable|null
    {
        $this->factory       = new HttpFactory((new RulesBagFactory)->createRulesBag(new ReflectionClass(HttpRules::class)));
        $this->routerFactory = new RouterFactory();
        $urlfactory          = new HttpUrlFactory($this->factory);

        if ($this->routerFactory->haveMounts() === false) {
            if (isset($this->httpUrl) or isset($this->httpsUrl)) {
                throw new WiringException("Http or https url is set but no mounts.");
            }
            return;
        }

        if (!isset($this->httpUrl) and !isset($this->httpsUrl)) {
            throw new WiringException("Mounts are set but no http or https url.");
        }

        $router     = $this->routerFactory->createRouter();
        $urlFactory = $wirings->get("kernel")->service("url-factory");
        foreach ([$this->httpUrl, $this->httpsUrl] as $url) {
            if ($url === null) continue;
            $urlFactory
                ->createUrl($url)
                ->bind($urlFactory->createUrl("activity:"))
                ->listen(new HttpAcceptor(
                    factory: $this->factory,
                    router:  $router,
                    httpUrl: $url,
                ));
        }
    }

    public function service(string $key): object
    {
        return match ($key) {
            default  => throw new NoSuchServiceWiringException($key),
        };
    }
}
