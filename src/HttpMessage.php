<?php

declare(strict_types=1);

namespace davekok\http;

abstract class HttpMessage
{
    public const OPTIONS        = "OPTIONS";
    public const HEAD           = "HEAD";
    public const GET            = "GET";
    public const PUT            = "PUT";
    public const POST           = "POST";
    public const PATCH          = "PATCH";
    public const DELETE         = "DELETE";

    public const ACCEPT         = "Accept";
    public const ALLOW          = "Allow";
    public const CONTENT_LENGTH = "Content-Length";
    public const CONTENT_TYPE   = "Content-Type";
    public const DATE           = "Date";
    public const ETAG           = "ETag";
    public const HOST           = "Host";
    public const LAST_MODIFIED  = "Last-Modified";
    public const SERVER         = "Server";

    public function __construct(
        public readonly float|null  $protocolVersion = null,
        public readonly array       $headers         = [],
        public readonly string|null $body            = null,
    ) {}

    private const REGEX =
        "~ *(application|audio|example|font|image|message|model|multipart|text|*)/([A-Za-z0-9._-]+|*)(?:;q=(1|0\.[0-9]+))?~";

    public function accept(array $supported): string|null
    {
        if (isset($this->headers[self::ACCEPT]) === false) {
            return null;
        }
        $firstMimeType    = null;
        $firstSubMimeType = [];
        foreach ($supported as $mimeType) {
            [$mimeType, $subMimeType] = explode("/", $mimeType);
            if (isset($firstMimeType) === false) {
                $firstMimeType = $mimeType;
            }
            if (isset($firstSubMimeType[$mimeType]) === false) {
                $firstSubMimeType[$mimeType] = $subMimeType;
            }
        }
        $currentQuality = 0;
        $use            = null;
        foreach (explode("," $accept) as $mimeType) {
            if (preg_match(self::REGEX, $mimeType, $matches) === 1) {
                $mimeType    = $matches[1];
                $subMimeType = $matches[2];
                $quality     = (float)($matches[3] ?? 1);
                if ($mimeType === "*") {
                    $mimeType = $firstMimeType;
                }
                if ($subMimeType === "*") {
                    $mimeType = $firstSubMimeType[$subMimeType];
                }
                if ($currentQuality < $quality && in_array($mimeType, $supported) === true) {
                    $currentQuality = $quality;
                    $use            = $mimeType;
                }
            }
        }
        return $mimeType;
    }

    public function contentType(): string
    {
        return $this->headers[self::CONTENT_TYPE] ?? "application/octet-stream";
    }
}
