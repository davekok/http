<?php

declare(strict_types=1);

namespace davekok\http\tests;

use davekok\http\HttpContainerFactory;
use davekok\http\HttpFilter;
use davekok\http\HttpMessage;
use davekok\http\HttpReader;
use davekok\http\HttpRequest;
use davekok\http\HttpResponse;
use davekok\http\HttpStatus;
use davekok\kernel\Actionable;
use davekok\kernel\ActiveSocket;
use davekok\kernel\Readable;
use davekok\kernel\ReadBuffer;
use davekok\kernel\Url;
use davekok\parser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\http\HttpReader
 */
class ReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
                headers: [
                    "Host" => "davekok.http.example",
                ],
            ),
            $this->read("HTTP/1.1 204 No Content\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
            ),
            $this->read("HTTP/1.1 204 No Content\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
        static::assertEquals(
            new HttpResponse(
                status: HttpStatus::NO_CONTENT,
                protocolVersion: 1.1,
                headers: [
                    "Date" => "today",
                    "Host" => "davekok.http.exemple",
                ]
            ),
            $this->read("HTTP/1.1 204 No Content\r\nDate: today\r\nHost: davekok.http.exemple\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read("GET /some/path HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read("GET /some/path?sdf=sdf HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read("GET /some/path HTTP/1.1\r\nHost: davekok.http.example:28437\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read("GET /some/path HTTP/1.1\r\nHost: davekok.https.example\r\n\r\n", true)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read("GET /some/path HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequestNoHeaders
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     */
    public function testReadRequestNoHeaders(): void
    {
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            $this->read("GET /some/path HTTP/1.1\r\n\r\n")
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
     * @covers \davekok\http\HttpMessage::__construct
     * @covers \davekok\http\HttpRequest::__construct
     * @covers \davekok\http\HttpRules::__construct
     * @covers \davekok\http\HttpRules::createRequestNoHeaders
     * @covers \davekok\http\HttpRules::createRequestLine
     * @covers \davekok\http\HttpRules::setParser
     */
    public function testReadRequestNoHeadersCryptoEnabled(): void
    {
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "https",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            $this->read("GET /some/path HTTP/1.1\r\n\r\n", true)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
        static::assertEquals(
            new HttpRequest(
                method: HttpMessage::GET,
                url: new Url(
                    scheme: "http",
                    path: "/some/path",
                ),
                protocolVersion: 1.1,
            ),
            $this->read($text)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::read
     * @covers \davekok\http\HttpContainerFactory::__construct
     * @covers \davekok\http\HttpContainerFactory::createContainer
     * @covers \davekok\http\HttpContainer::__construct
     * @covers \davekok\http\HttpFilter::__construct
     * @covers \davekok\http\HttpFilter::createReader
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
            $this->read($text)
        );
    }

    private function read(string $text, bool $isCryptoEnabled = false): HttpMessage
    {
        return (new HttpContainerFactory)
            ->createContainer()
            ->filter
            ->createReader($isCryptoEnabled)
            ->read(new ReadBuffer($text));
    }
}
