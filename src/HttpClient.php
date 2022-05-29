<?php

declare(strict_types=1);

namespace davekok\http;

class HttpClient
{
    public function __construct(
        public readonly StreamConnector $connector = new SimpleStreamConnector()
    ) {}

    public function send(HttpRequest $request): HttpResponse
    {
        $stream = $this->connector->connect($request->getSocketAddress());
        $stream->write((new HttpWriter)->write([$request]));
        foreach ((new HttpReader)->read($stream->read()) as $response) {
            return $response;
        }
        throw new \RuntimeException("No response received");
    }
}
