<?php

declare(strict_types=1);

namespace davekok\http\tests;

use davekok\http\HttpReader;
use davekok\lalr1\Parser;
use davekok\stream\Activity;
use davekok\stream\ReaderBuffer;
use davekok\stream\StreamKernelReaderBuffer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \davekok\http\HttpReader
 */
class ReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::read
     */
    public function testReadResponse(): void
    {
        $text   = "HTTP/1.1 204 No Content\r\nHost: davekok.http.example\r\n\r\n";
        $parser = $this->createMock(Parser::class);
        $parser->expects(static::exactly(8))
            ->method("pushToken")
            ->withConsecutive(
                ["version", 1.1],
                ["status-code", 204],
                ["status-text", "No Content"],
                ["nl"],
                ["header-name", "Host"],
                ["header-value", "davekok.http.example"],
                ["nl"],
                ["nl"],
            );
        $parser->expects(static::once())->method("endOfTokens");

        $reader = new HttpReader($parser, $this->createMock(Activity::class));
        $reader->read(new StreamKernelReaderBuffer($text));
    }

    /**
     * @covers ::__construct
     * @covers ::read
     */
    public function testReadRequest(): void
    {
        $text   = "GET /some/path?sdf=sdf HTTP/1.1\r\nHost: davekok.http.example\r\n\r\n";
        $parser = $this->createMock(Parser::class);
        $parser->expects(static::exactly(9))
            ->method("pushToken")
            ->withConsecutive(
                ["method", "GET"],
                ["path", "/some/path"],
                ["query", "sdf=sdf"],
                ["version", 1.1],
                ["nl"],
                ["header-name", "Host"],
                ["header-value", "davekok.http.example"],
                ["nl"],
                ["nl"],
            );
        $parser->expects(static::once())->method("endOfTokens");

        $reader = new HttpReader($parser, $this->createMock(Activity::class));
        $reader->read(new StreamKernelReaderBuffer($text));
    }
}
