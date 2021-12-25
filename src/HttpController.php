<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Writer;

/**
 * A general purpose http controller. Simply configure what you need and let it do the rest.
 */
class HttpController
{
    private array $baseHeaders;
    private array $contentHeaders;
    private array $allowedMethods = ["OPTIONS", "GET", "HEAD"];
    private array $supportedContentTypes = [];

    public function __construct(
        private readonly string $server,
        private readonly HttpRequest $request,
    ) {
        $this->baseHeaders = [
            HttpMessage::DATE   => str_replace("+0000", "GMT", date("r")),
            HttpMessage::HOST   => $this->request->host,
            HttpMessage::SERVER => $this->server,
        ];
    }

    public function allow(string ...$allowedMethods): self
    {
        $this->allowedMethods = $allowedMethods;
        return $this;
    }

    public function support(string ...$supportedContentTypes): self
    {
        $this->supportedContentTypes = $supportedContentTypes;
        return $this;
    }

    public function body(string|Writer $body, string $type, string $hash, int $length): self
    {
        $this->body = $body;

        $this->contentHeaders[HttpMessage::ETAG]           = $hash;
        $this->contentHeaders[HttpMessage::CONTENT_TYPE]   = $type;
        $this->contentHeaders[HttpMessage::CONTENT_LENGTH] = $length;

        if (empty($this->supportedContentTypes)) {
            $this->supportedContentTypes = [$type];
        }

        return $this;
    }

    public function text(string $text, string $type = "text/plain; charset=UTF-8"): self
    {
        return $this->body($text, $type, hash('sha3-256', $text), strlen($text));
    }

    public function csv(array $data): self
    {
        $file = fopen('php://memory', 'r+');
        foreach ($data as $row) {
            fputcsv($file, $row) ?: throw new Exception("Unable to format CSV data.");
        }
        rewind($file);
        $body = stream_get_contents($file);
        fclose($file);

        return $this->body($body, "text/csv; charset=UTF-8", hash('sha3-256', $body), strlen($body));
    }

    public function html(string $html): self
    {
        return $this->body($html, "text/html; charset=UTF-8", hash('sha3-256', $html), strlen($html));
    }

    public function css(string $css): self
    {
        return $this->body($css, "text/css; charset=UTF-8", hash('sha3-256', $css), strlen($css));
    }

    public function javascript(string $script): self
    {
        return $this->body($script, "application/javascript; charset=UTF-8", hash('sha3-256', $script), strlen($script));
    }

    public function json(mixed $value): self
    {
        $body = json_encode($value, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);

        return $this->body($body, "application/json", hash('sha3-256', $body), strlen($body));
    }

    public function file(Writer $writer): self
    {
        $this->body = $writer;
        $this->contentHeaders[HttpMessage::CONTENT_TYPE] = "application/octet-stream";
        return $this;
    }

    public function type(string $type): self
    {
        $this->contentHeaders[HttpMessage::CONTENT_TYPE] = $type;
        return $this;
    }

    public function length(int $length): self
    {
        $this->contentHeaders[HttpMessage::CONTENT_LENGTH] = $length;
        return $this;
    }

    public function hash(string $hash): self
    {
        $this->contentHeaders[HttpMessage::ETAG] = $hash;
        return $this;
    }

    public function lastModified(DateTime $lastModified): self
    {
        $this->contentHeaders[HttpMessage::LAST_MODIFIED] = str_replace("+0000", "GMT", $lastModified->format("r"));
        return $this;
    }

    public function filename(string $fileName, bool $attachment = false): self
    {
        $this->contentHeaders[HttpMessage::CONTENT_DISPOSITION] = ($attachment ? "attachment" : "inline") . "; filename=\"$fileName\"";
        return $this;
    }

    public function response(): HttpResponse
    {
        if (in_array($this->request->method, $this->allowed)) {
            return match ($this->request->method) {
                HttpMessage::OPTIONS => $this->createResponseToOptionsRequest(),
                HttpMessage::GET     => $this->createResponseToGetRequest(),
                HttpMessage::HEAD    => $this->createResponseToHeadRequest(),
                HttpMessage::PATCH   => $this->createResponseToPatchRequest(),
                HttpMessage::PUT     => $this->createResponseToPutRequest(),
                HttpMessage::POST    => $this->createResponseToPostRequest(),
                HttpMessage::DELETE  => $this->createResponseToDeleteRequest(),
                default              => $this->createMethodNotAllowedResponse(),
            };
        }
        return $this->createMethodNotAllowedResponse();
    }

    public function createResponseToOptionsRequest(): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::NO_CONTENT,
            protocolVersion: $this->request->protocolVersion,
            body:            null,
            headers:         [
                ...$this->baseHeaders,
                HttpMessage::ALLOW => implode(", ", $this->allowedMethods),
            ],
        );
    }

    public function badRequest(string $reason): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::BAD_REQUEST,
            protocolVersion: $this->request->protocolVersion,
            headers:         $this->baseHeaders,
            body:            <<<TEXT
                             Bad Request
                             -----------
                             {$reason}
                             TEXT
        );
    }

    public function notFound(): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::NOT_FOUND,
            protocolVersion: $this->request->protocolVersion,
            headers:         $this->baseHeaders,
            body:            null,
        );
    }

    public function notAcceptable(): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::NOT_ACCEPTABLE,
            protocolVersion: $this->request->protocolVersion,
            headers:         $this->baseHeaders,
            body:            null,
        );
    }

    public function methodNotAllowed(): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::METHOD_NOT_ALLOWED,
            protocolVersion: $this->request->protocolVersion,
            headers:         $this->baseHeaders,
            body:            <<<TEXT
                             Method not allowed
                             ------------------
                             {$this->request->method}

                             Allowed methods
                             ---------------
                             {$this->formatList($this->allowed)}
                             TEXT
        );
    }

    public function unsupportedMediaType(): HttpResponse
    {
        return new HttpResponse(
            status:          HttpStatus::UNSUPPORTED_MEDIA_TYPE,
            protocolVersion: $this->request->protocolVersion,
            headers:         $this->baseHeaders,
            body:            <<<TEXT
                             Unsupported media type
                             ----------------------
                             {$this->request->headers[HttpMessage::CONTENT_TYPE]}

                             Supported media types
                             ---------------------
                             {$this->formatList($this->supportedContentTypes)}
                             TEXT
        );
    }

    private function formatList(array $list): string
    {
        return "- " . implode("\n- ", $list) . "\n";
    }
}
