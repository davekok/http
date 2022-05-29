<?php

declare(strict_types=1);

namespace davekok\http;

use LogicException;

class HttpRequest extends HttpMessage
{
    public readonly string $method;
    public readonly string $scheme;
    public readonly string $host;
    public readonly string $path;
    public readonly array $query;

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function setScheme(string $scheme): static
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;
        return $this;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function setQuery(array $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function text(): string
    {
        if ($this->body === null) {
            throw new LogicException("empty body");
        }
        $text = "";
        /** @noinspection PhpLoopCanBeReplacedWithImplodeInspection */
        foreach ($this->body as $chunk) {
            $text .= $chunk;
        }
        return $text;
    }

    public function json(): string
    {
        return json_decode(
            json: $this->text(),
            flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * This function is mostly useful from the client perspective.
     * When a client wishes to send a request.
     */
    public function getSocketAddress(): string
    {
        $address = "tcp://$this->host";
        if (!str_contains($this->host, ":")) {
            $address .= $this->scheme === "https" ? "443" : "80";
        }
        return $address;
    }
}
