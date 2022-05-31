<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;

/**
 * Reference implementation of a http server.
 */
class HttpServer
{
    public function __construct(
        public readonly HttpRequestHandler $handler,
        public readonly string $scheme,
        public readonly string $host,
        public readonly array $defaultResponseHeaders = [],
    ) {}

    public function process(Generator $input): Generator
    {
        yield from (new HttpFormatter)->format($this->handleMessages($input));
    }

    private function handleMessages(Generator $input): Generator
    {
        /** @var HttpRequest|HttpResponse $request */
        foreach ((new HttpParser)->parse($input) as $request) {
            if ($request === null) {
                return;
            }
            if ($request instanceof HttpResponse) {
                error_log("Received http response while http request was expected.");
                continue;
            }
            if (!isset($request->scheme)) {
                $request->setScheme($this->scheme);
            }
            if (!isset($request->host)) {
                $request->setHost($this->host);
            }
            $response = $this->handler->handleRequest($request);
            if (!isset($response->protocol)) {
                $response->setProtocol($request->protocol);
            }
            $headers = $response->getHeaders();
            $headers->date ??= date("r");
            foreach ($this->defaultResponseHeaders as $key => $value) {
                $headers->$key ??= $value;
            }
            yield $response;
        }
    }
}
