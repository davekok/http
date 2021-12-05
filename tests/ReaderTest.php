<?php

declare(strict_types=1);

namespace davekok\http\tests;

use davekok\http\HttpComponent;
use davekok\http\HttpMessage;
use davekok\http\HttpReader;
use davekok\http\HttpRequest;
use davekok\http\HttpResponse;
use davekok\http\HttpStatus;
use davekok\kernel\Actionable;
use davekok\kernel\ActiveSocket;
use davekok\kernel\ReadBuffer;
use davekok\kernel\Url;
use davekok\lalr1\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\http\HttpReader
 */
class ReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpResponse::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createResponse
     * @covers \davekok\http\HttpRules::createResponseLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     * @covers \davekok\http\HttpStatus::text
     */
    public function testReadResponse(): void
    {
        $text = "HTTP/1.1 204 No Content\r\nHost: davekok.http.example\r\n\r\n";
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example",
                ],
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpResponse::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createResponseNoHeaders
     * @covers \davekok\http\HttpRules::createResponseLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpStatus::text
     */
    public function testReadResponseNoHeaders(): void
    {
        $text = "HTTP/1.1 204 No Content\r\n\r\n";
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpResponse::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createResponse
     * @covers \davekok\http\HttpRules::createResponseLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     * @covers \davekok\http\HttpRules::addHeader
     * @covers \davekok\http\HttpStatus::text
     */
    public function testReadResponseMultipleHeaders(): void
    {
        $text = "HTTP/1.1 204 No Content\r\nDate: today\r\nHost: davekok.http.exemple\r\n\r\n";
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
                headers: [
                    "Date" => "today",
                    "Host" => "davekok.http.exemple",
                ]
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequest(): void
    {
        $text = "GET /some/path HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n";
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    host: "davekok.http.example",
                    port: 80,
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example",
                ],
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLineWithQuery
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequestWithQuery(): void
    {
        $text = "GET /some/path?sdf=sdf HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n";
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    host: "davekok.http.example",
                    port: 80,
                    path: "/some/path",
                    query: "sdf=sdf",
                ),
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example",
                ],
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequestWithPort(): void
    {
        $text = "GET /some/path HTTP/1.1\r\nHost: davekok.http.example:28437\r\n\r\n";
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    host: "davekok.http.example",
                    port: 28437,
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example:28437",
                ],
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequestFromCryptobleSourceEnabled(): void
    {
        $text = "GET /some/path HTTP/1.1\r\nHost: davekok.https.example\r\n\r\n";
        $socket = $this->createMock(ActiveSocket::class);
        $socket->expects(static::once())->method('isCryptoEnabled')->willReturn(true);
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "https",
                    host: "davekok.https.example",
                    port: 443,
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.https.example",
                ],
            ),
            (new HttpComponent)->createReader($socket)->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequestFromCryptobleSourceDisabled(): void
    {
        $text = "GET /some/path HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n";
        $socket = $this->createMock(ActiveSocket::class);
        $socket->expects(static::once())->method('isCryptoEnabled')->willReturn(false);
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    host: "davekok.http.example",
                    port: 80,
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example",
                ],
            ),
            (new HttpComponent)->createReader($socket)->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequestNoHeaders
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     */
    public function testReadRequestNoHeaders(): void
    {
        $text = "GET /some/path HTTP/1.1\r\n\r\n";
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequestNoHeaders
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     */
    public function testReadRequestNoHeadersCryptoEnabled(): void
    {
        $text = "GET /some/path HTTP/1.1\r\n\r\n";
        $socket = $this->createMock(ActiveSocket::class);
        $socket->expects(static::once())->method('isCryptoEnabled')->willReturn(true);
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "https",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            (new HttpComponent)->createReader($socket)->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequestNoHeaders
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     */
    public function testReadRequestNoHeadersCryptoDisabled(): void
    {
        $text = "GET /some/path HTTP/1.1\r\n\r\n";
        $socket = $this->createMock(ActiveSocket::class);
        $socket->expects(static::once())->method('isCryptoEnabled')->willReturn(false);
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            (new HttpComponent)->createReader($socket)->read(new ReadBuffer($text))
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpComponent::__construct
     * @covers \davekok\http\HttpComponent::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequest
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     * @covers \davekok\http\HttpRules::createHeaders
     */
    public function testReadRequestNoHost(): void
    {
        $text = "GET /some/path HTTP/1.1\r\nSome: header\r\n\r\n";
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
                headers: ["Some" => "header"],
            ),
            (new HttpComponent)->createReader($this->createMock(Actionable::class))->read(new ReadBuffer($text))
        );
    }
}
