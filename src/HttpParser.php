<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\parser\ParserTrait;
use davekok\parser\attributes\{Rule,Input,Output,Type,Parser};
use stdClass;

#[Parser(lexar: true)]
#[Output("Message")]
#[Type("RequestLine")]
#[Type("ResponseLine")]
#[Input("StatusCode")]
#[Input("StatusText")]
#[Input("Method")]
#[Input("Path")]
#[Input("Query")]
#[Input("Protocol")]
#[Type("Headers")]
#[Input("HeaderKey")]
#[Input("HeaderValue")]
#[Input("NewLine")]
class HttpParser
{
    use ParserTrait;
    use HttpParserStitcher;
    use HttpParserLexar;

    #[Rule("Message", "RequestLine Headers NewLine")]
    public function createRequest(HttpRequest $request1, object $headers2): HttpRequest
    {
        return $this->fixRequest($request1->setHeaders($headers2));
    }

    #[Rule("Message", "RequestLine NewLine")]
    public function createRequestNoHeaders(HttpRequest $requestLine1): HttpRequest
    {
        return $this->fixRequest($requestLine1->setHeaders(new stdClass));
    }

    private function fixRequest(HttpRequest $request): HttpRequest
    {
        if (isset($request->headers->host)) {
            $request->setHost($request->headers->host);
        }
        return $this->fixBody($request);
    }

    #[Rule("Message", "ResponseLine Headers NewLine")]
    public function createResponse(HttpResponse $requestLine1, object $headers2): HttpResponse
    {
        return $this->fixBody($requestLine1->setHeaders($headers2));
    }

    #[Rule("Message", "ResponseLine NewLine")]
    public function createResponseNoHeaders(HttpResponse $responseLine1): HttpResponse
    {
        return $this->fixBody($responseLine1->setHeaders(new stdClass));
    }

    private function fixBody(HttpRequest|HttpResponse $message): HttpRequest|HttpResponse
    {
        $contentLength = $message->headers->contentLength ?? 0;
        return $message->setBody($contentLength ? $this->getBody($contentLength) : null);
    }

    #[Rule("RequestLine", "Method Path Query Protocol NewLine")]
    public function createRequestLineWithQuery(string $method1, string $path2, array $query3, string $protocol4): HttpRequest
    {
        return (new HttpRequest)
            ->setMethod($method1)
            ->setPath($path2)
            ->setQuery($query3)
            ->setProtocol($protocol4);
    }

    #[Rule("RequestLine", "Method Path Protocol NewLine")]
    public function createRequestLine(string $method1, string $path2, string $protocol3): HttpRequest
    {
        return (new HttpRequest)
            ->setMethod($method1)
            ->setPath($path2)
            ->setQuery([])
            ->setProtocol($protocol3);
    }

    #[Rule("ResponseLine", "Protocol StatusCode StatusText NewLine")]
    public function createResponseLine(string $protocol1, int $statusCode2, string $statusText3): HttpResponse
    {
        return (new HttpResponse)
            ->setProtocol($protocol1)
            ->setStatus(HttpStatus::tryFrom($statusCode2) ?? throw new HttpException("Unknown status code $statusCode2 $statusText3"));
    }

    #[Rule("Headers", "HeaderKey HeaderValue NewLine")]
    public function createHeaders(string $headerKey1, string $headerValue2): stdClass
    {
        $headers = new stdClass;
        $headers->{HttpMessage::snakeCaseToCamelCase($headerKey1)} = HttpMessage::castHeaderValue($headerValue2);
        return $headers;
    }

    #[Rule("Headers", "Headers HeaderKey HeaderValue NewLine")]
    public function addHeader(stdClass $headers1, string $headerKey2, string $headerValue3): stdClass
    {
        $headers1->{HttpMessage::snakeCaseToCamelCase($headerKey2)} = HttpMessage::castHeaderValue($headerValue3);
        return $headers1;
    }
}
