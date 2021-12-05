<?php

declare(strict_types=1);

namespace davekok\http;

use DateTime;
use Exception;

class HttpFormatter
{
    public function formatRequestLine(string $method, string|null $path, string|null $query, float $protocolVersion): string
    {
        return $method
            . " "
            . ($path ?? "/")
            . ($query !== null ? ("?" . $query) : "")
            . " "
            . $this->formatProtocolVersion($protocolVersion)
            . "\r\n";
    }

    public function formatResponseLine(float $protocolVersion, HttpStatus $status): string
    {
        return $this->formatProtocolVersion($protocolVersion)
            . " "
            . $status->code()
            . " "
            . $status->text()
            . "\r\n";
    }

    public function formatHeader(string $name, string $value): string
    {
        return "$name:$value\r\n";
    }

    public function formatEndOfHeaders(): string
    {
        return "\r\n";
    }

    private function formatProtocolVersion(float $protocolVersion): string
    {
        if ($protocolVersion !== 1.0 && $protocolVersion !== 1.1) {
            throw new Exception("Invalid HTTP protocol version: {$protocolVersion}");
        }
        return "HTTP/{$protocolVersion}";
    }

    /**
     * Should only be used for debugging and testing.
     */
    public function format(HttpMessage $message): string
    {
        if ($message instanceof HttpRequest) {
            return $this->formatRequestLine($message->method, $message->path, $message->query, $message->protocolVersion)
                . $this->formatHeaders()
                . $message->body;
        } else {
            return
                $this->formatResponseLine($message->protocolVersion, $message->status)
                . $this->formatHeaders()
                . $message->body;
        }
    }

    private function formatHeaders(array $headers): string
    {
        $ret = "";
        foreach ($headers as $name => $value) {
            $ret .= $this->formatHeader($name, $value);
        }
        $ret .= $this->formatEndOfHeaders();
        return $ret;
    }
}
