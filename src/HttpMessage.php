<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;
use stdClass;

abstract class HttpMessage
{
    public readonly string $protocol;
    public readonly object $headers;
    public readonly Generator|null $body;

    public function setProtocol(string $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    public function setHeaders(object $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    public function setHeader(string $key, string $value): static
    {
        $headers = $this->headers ??= new stdClass;
        $headers->{self::snakeCaseToCamelCase($key)} = self::castHeaderValue($value);
        return $this;
    }

    public function setBody(Generator|null $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getHeaders(): stdClass
    {
        return $this->headers ??= new stdClass;
    }

    public static function snakeCaseToCamelCase(string $header): string
    {
        if (!str_contains($header, "-")) {
            return lcfirst($header);
        }
        return str_replace("-", "", lcfirst(ucwords(strtolower($header), "-")));
    }

    public static function camelCaseToSnakeCase($string): string
    {
        return ucfirst(preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', "-", $string));
    }

    public static function castHeaderValue(string $headerValue): string|int
    {
        if (ctype_digit($headerValue) && $headerValue[0] !== '0') {
            return (int)$headerValue;
        }
        return $headerValue;
    }
}
