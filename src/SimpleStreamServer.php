<?php

declare(strict_types=1);

namespace davekok\http;

use Throwable;

/**
 * Example implementation of a simple stream server.
 */
class SimpleStreamServer
{
    public function __construct(
        public readonly HttpServer $httpServer,
    ) {}

    public function listen(string $bind = "tcp://0.0.0.0:8080"): void
    {
        $server = stream_socket_server($bind, $errorCode, $errorMessage)
            ?: throw new \RuntimeException($errorMessage, $errorCode);
        for ($i = 0; $i < 3; ++$i) {
            $stream = stream_socket_accept($server, null);

            // Only three attempts are made to accept streams.
            if ($stream === false) {
                continue;
            }
            $i = 0;

            try {
                $stream = new SimpleStream($stream);
                $stream->write($this->httpServer->process($stream->read()));
            } catch (Throwable $throwable) {
                error_log($throwable->getMessage());
            }
        }
    }
}
