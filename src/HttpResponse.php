<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;
use SplFileObject;

class HttpResponse extends HttpMessage
{
    public readonly HttpStatus $status;

    public function setStatus(HttpStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public static function text(string $text, string $contentType = "text/plain; charset=utf-8", HttpStatus $status = HttpStatus::OK): self
    {
        $response = new self();
        $response->setStatus($status);
        $headers = $response->getHeaders();
        $headers->contentType = $contentType;
        $headers->contentLength = strlen($text);
        $response->setBody(self::textBody($text));
        return $response;
    }

    public static function html(string $html, HttpStatus $status = HttpStatus::OK): self
    {
        return self::text(
            text: $html,
            contentType: "text/html; charset=utf-8",
            status: $status,
        );
    }

    public static function json(mixed $json, string $contentType = "application/json", HttpStatus $status = HttpStatus::OK): self
    {
        return self::text(
            text: json_encode(
                value: $json,
                flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
            ),
            contentType: $contentType,
            status: $status,
        );
    }

    public static function file(string $file, string $contentType = "application/octet-stream", HttpStatus $status = HttpStatus::OK): self
    {
        $response = new self();
        $response->setStatus($status);
        $headers = $response->getHeaders();
        $headers->contentType = $contentType;
        $headers->contentLength = filesize($file);
        $response->setBody(self::fileBody($file));
        return $response;
    }

    private static function textBody(string $text): Generator
    {
        yield $text;
    }

    private static function fileBody(string $file): Generator
    {
        $file = new SplFileObject($file, "r");
        while ($chunk = $file->fread(8192)) {
            yield $chunk;
        }
    }
}
