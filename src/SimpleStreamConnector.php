<?php

declare(strict_types=1);

namespace davekok\http;

class SimpleStreamConnector implements StreamConnector
{
    public function connect(string $address, float|null $timeout = null): Stream
    {
        return new SimpleStream(stream_socket_client($address, $errorCode, $errorMessage, $timeout)
            ?: throw new \RuntimeException($errorMessage, $errorCode));
    }
}
