<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Writer;
use davekok\kernel\WriteBuffer;
use ArrayIterator;

enum HttpWriter_State
{
    case REQUEST_LINE;
    case RESPONSE_LINE;
    case HEADERS;
    case HEADER;
    case NEXT_HEADER;
    case END_OF_HEADERS;
    case START_BODY;
    case BODY;
}

class HttpWriter implements Writer
{
    private HttpWriter_State $state;
    private ArrayIterator $headers;
    private int $offset;

    public function __construct(
        private readonly HttpMessage   $message,
        private readonly HttpFormatter $formatter = new HttpFormatter,
    ) {}

    public function write(WriteBuffer $buffer): bool
    {
        for (;;) {
            switch ($this->state) {
                case HttpWriter_State::REQUEST_LINE:
                    $requestLine = $this->formatter->formatRequestLine(
                        $this->message->method,
                        $this->message->path,
                        $this->message->query,
                        $this->message->protocolVersion,
                    );
                    if ($buffer->valid(strlen($requestLine)) === false) {
                        return false;
                    }
                    $buffer->add($requestLine);
                    $this->state = HttpWriter_State::HEADERS;
                case HttpWriter_State::RESPONSE_LINE:
                    $responseLine = $this->formatter->formatResponseLine(
                        $this->message->protocolVersion,
                        $this->message->status,
                    );
                    if ($buffer->valid(strlen($responseLine)) === false) {
                        return false;
                    }
                    $buffer->add($responseLine);
                    $this->state = HttpWriter_State::HEADERS;
                case HttpWriter_State::HEADERS:
                    $this->headers = new ArrayIterator($this->message->headers);
                    if ($this->headers->valid() === false) {
                        $this->state = HttpWriter_State::END_OF_HEADERS;
                        continue 2;
                    }
                    $this->state = HttpWriter_State::HEADER;
                case HttpWriter_State::HEADER:
                    $header = $this->formatter->formatHeader($this->headers->current());
                    if ($buffer->valid(strlen($header)) === false) {
                        return false;
                    }
                    $buffer->add($header);
                    $this->state = HttpWriter_State::NEXT_HEADER;
                case HttpWriter_State::NEXT_HEADER:
                    $this->headers->next();
                    if ($this->headers->valid() === true) {
                        $this->state = HttpWriter_State::HEADER;
                        continue 2;
                    }
                    $this->state = HttpWriter_State::END_OF_HEADERS;
                case HttpWriter_State::END_OF_HEADERS:
                    $endOfHeaders = $this->formatter->formatEndOfHeaders();
                    if ($buffer->valid(strlen($endOfHeaders)) === false) {
                        return false;
                    }
                    $buffer->add($endOfHeaders);
                    $this->state = HttpWriter_State::START_BODY;
                case HttpWriter_State::START_BODY:
                    if ($this->message->body === null) {
                        return true;
                    }
                    $this->offset = 0;
                    $this->state = HttpWriter_State::BODY;
                case HttpWriter_State::BODY:
                    if ($buffer->addChunk($this->offset, $this->message->body)) {
                        return false;
                    }
                    return true;
            }
        }
    }
}
