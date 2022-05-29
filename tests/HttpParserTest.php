<?php

declare(strict_types=1);

namespace davekok\http\tests;

use davekok\http\HttpMessage;
use davekok\http\HttpParser;
use davekok\http\HttpRequest;
use davekok\http\HttpResponse;
use davekok\http\HttpStatus;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass \davekok\http\HttpParser
 * @covers ::addHeader
 * @covers ::createHeaders
 * @covers ::createRequest
 * @covers ::createRequest
 * @covers ::createRequestLine
 * @covers ::createRequestLineWithQuery
 * @covers ::createRequestNoHeaders
 * @covers ::createResponse
 * @covers ::createResponseLine
 * @covers ::createResponseNoHeaders
 * @covers ::fixBody
 * @covers ::fixRequest
 * @covers ::parse
 * @covers \davekok\http\HttpMessage::castHeaderValue
 * @covers \davekok\http\HttpMessage::setBody
 * @covers \davekok\http\HttpMessage::setHeader
 * @covers \davekok\http\HttpMessage::setHeaders
 * @covers \davekok\http\HttpMessage::setProtocol
 * @covers \davekok\http\HttpMessage::snakeCaseToCamelCase
 * @covers \davekok\http\HttpParserLexar::getString
 * @covers \davekok\http\HttpParserLexar::lex
 * @covers \davekok\http\HttpParserRule::precedence
 * @covers \davekok\http\HttpParserStitcher::findRule
 * @covers \davekok\http\HttpParserStitcher::reduce
 * @covers \davekok\http\HttpParserToken::__construct
 * @covers \davekok\http\HttpParserType::input
 * @covers \davekok\http\HttpParserType::key
 * @covers \davekok\http\HttpParserType::output
 * @covers \davekok\http\HttpParserType::precedence
 * @covers \davekok\http\HttpRequest::setHost
 * @covers \davekok\http\HttpRequest::setMethod
 * @covers \davekok\http\HttpRequest::setPath
 * @covers \davekok\http\HttpRequest::setQuery
 * @covers \davekok\http\HttpResponse::setStatus
 */
class HttpParserTest extends TestCase
{
    public function testReadResponse(): void
    {
        static::assertEquals(
            (new HttpResponse)
                ->setStatus(HttpStatus::NO_CONTENT)
                ->setProtocol("HTTP/1.1")
                ->setHeader("Host", "davekok.http.example")
                ->setBody(null),
            $this->parse("HTTP/1.1 204 No Content\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    public function testReadResponseNoHeaders(): void
    {
        static::assertEquals(
            (new HttpResponse)
                ->setStatus(HttpStatus::NO_CONTENT)
                ->setProtocol("HTTP/1.1")
                ->setHeaders(new stdClass)
                ->setBody(null),
            $this->parse("HTTP/1.1 204 No Content\r\n\r\n")
        );
    }

    public function testReadResponseMultipleHeaders(): void
    {
        static::assertEquals(
            (new HttpResponse)
                ->setStatus(HttpStatus::NO_CONTENT)
                ->setProtocol("HTTP/1.1")
                ->setHeader("Date", "today")
                ->setHeader("Host", "davekok.http.example")
                ->setBody(null),
            $this->parse("HTTP/1.1 204 No Content\r\nDate: today\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    public function testReadRequest(): void
    {
        static::assertEquals(
            (new HttpRequest)
                ->setMethod("GET")
                ->setHost("davekok.http.example")
                ->setPath("/some/path")
                ->setQuery([])
                ->setProtocol("HTTP/1.1")
                ->setHeader("Host", "davekok.http.example")
                ->setBody(null),
            $this->parse("GET /some/path HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    public function testReadRequestWithQuery(): void
    {
        static::assertEquals(
            (new HttpRequest)
                ->setMethod("GET")
                ->setHost("davekok.http.example")
                ->setPath("/some/path")
                ->setQuery(["sdf"=>"sdf"])
                ->setProtocol("HTTP/1.1")
                ->setHeader("Host", "davekok.http.example")
                ->setBody(null),
            $this->parse("GET /some/path?sdf=sdf HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    public function testReadRequestWithPort(): void
    {
        static::assertEquals(
          (new HttpRequest)
                ->setMethod("GET")
                ->setHost("davekok.http.example:28437")
                ->setPath("/some/path")
                ->setQuery([])
                ->setProtocol("HTTP/1.1")
                ->setHeader("Host", "davekok.http.example:28437")
                ->setBody(null),
            $this->parse("GET /some/path HTTP/1.1\r\nHost: davekok.http.example:28437\r\n\r\n")
        );
    }

    public function testReadRequestNoHeaders(): void
    {
        static::assertEquals(
            (new HttpRequest)
                ->setMethod("GET")
                ->setPath("/some/path")
                ->setQuery([])
                ->setProtocol("HTTP/1.1")
                ->setHeaders(new stdClass)
                ->setBody(null),
            $this->parse("GET /some/path HTTP/1.1\r\n\r\n")
        );
    }

    private function parse(string $text): HttpMessage
    {
        foreach ((new HttpParser)->parse([$text]) as $message) {
            return $message;
        }
        throw new \RuntimeException("Failed to parse text: $text");
    }
}
