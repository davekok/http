<?php

declare(strict_types=1,ticks=1);

namespace davekok\http;

use Throwable;
use Generator;

/**
 * Example implementation of a simple stream server.
 */
class SimpleStreamServer
{
    private bool $running = false;
    private mixed $server;
    private mixed $client;

    public function __construct(
        public readonly HttpServer $httpServer,
    ) {}

    public static function log(string $type, array $data): void
    {
        error_log(json_encode(
            value: ["date" => date('Y-m-d H:i:s'), "type" => $type, ...$data],
            flags: JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE
        ));
    }

    public function stop(): void
    {
        $this->log("info", ["message" => "shutting down"]);
        exit();
    }

    public function listen(string $bind = "tcp://0.0.0.0:8080"): void
    {
        $this->log("info", ["message" => "listening on $bind"]);

        // catch a bunch of common quit signals and stop running
        pcntl_signal(SIGHUP,  $this->stop(...));
        pcntl_signal(SIGQUIT, $this->stop(...));
        pcntl_signal(SIGTERM, $this->stop(...));
        pcntl_signal(SIGINT,  $this->stop(...));

        $this->server = stream_socket_server($bind, $errorCode, $errorMessage)
            ?: throw new \RuntimeException($errorMessage, $errorCode);

        $this->running = true;
        while ($this->running) {
            $this->client = @stream_socket_accept($this->server, null, $peerName);
            if ($this->client === false) {
                continue;
            }
            $this->log("info", ["peer" => $peerName, "message" => "$peerName connected"]);
            stream_set_blocking($this->client, false);

            try {
                $this->write($this->httpServer->process($this->read()));
            } catch (Throwable $throwable) {
                $this->log("error", [
                    "peer" => $peerName,
                    "file" => $throwable->getFile(),
                    "line" => $throwable->getLine(),
                    "message" => $throwable->getMessage(),
                    "trace" => $throwable->getTrace(),
                ]);
            }
        }
    }

    public function write(iterable $chunks): void
    {
        foreach ($chunks as $chunk) {
            $length = strlen($chunk);
            while ($length) {
                $read = [];
                $write = [$this->client];
                $except = [];
                if (stream_select($read, $write, $except, null) === false) {
                    if ($this->running === false) {
                        return;
                    }
                    continue;
                }
                $written = fwrite($this->client, $chunk, $length);
                if ($written === false) {
                }
                $length -= $written;
            }
        }
    }

    public function read(): Generator
    {
        while (!feof($this->client)) {
            $read = [$this->client];
            $write = [];
            $except = [];
            if (stream_select($read, $write, $except, null) === false) {
                if ($this->running === false) {
                    return;
                }
                continue;
            }
            $chunk = fread($this->client, 8192);
            if ($chunk === false) {
                fclose($this->client);
                $this->client = null;
                return;
            }
            yield $chunk;
        }
    }
}
