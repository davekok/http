<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\stream\Activity;
use davekok\stream\Reader;
use davekok\stream\ReaderBuffer;
use davekok\stream\ReaderException;

enum HttpReader_Condition
{
    case MAIN;
    case STATUS;
    case HEADER_VALUE;
}

enum HttpReader_State
{
    case YY_START;
    case YY_CODE;
    case YY_STATUS;
    case YY_METHOD_OR_HEADER_NAME;
    case YY_METHOD_VERSION_HEADER;
    case YY_VERSION;
    case YY_PATH;
    case YY_QUERY;
    case YY_HEADER_NAME;
    case YY_HEADER_SPACE;
    case YY_HEADER_VALUE;
    case YY_INDENT;
    case YY_NL;
    case YY_DOUBLE_NL_1;
    case YY_DOUBLE_NL_2;
}

class HttpReader implements Reader
{
    public function __construct(
        private Parser $parser,
        private Activity $activity,
        private HttpReader_Condition $condition = HttpReader_Condition::MAIN,
        private HttpReader_State $state = HttpReader_State::YY_START,
    ) {}

    public function receive(HttpRequestHandler|HttpResponseHandler $handler): void
    {
        $this->activity->andThenRead($this);
        if ($handler instanceof HttpRequestHandler) {
            $this->activity->andThen($handler->handleRequest(...));
        } else {
            $this->activity->andThen($handler->handleResponse(...));
        }
    }

    public function reset(ReaderBuffer $buffer): void
    {
        $buffer->reset();
        $this->parser->reset();
    }

    /**
     * Scan for HTTP tokens.
     *
     * [main] code = ([0-9]+) " " :=> status => pushToken("status-code", $1)
     * [status] status = ( [\x20-\x7E]+ ) :=> main => pushToken("status-text", $1)
     * [main] method = ([A-Za-z]+) " " => pushToken("method", $1)
     * [main] path = ("/" [\x21-\x7E]+) " " => pushToken("path", $1)
     * [main] header-name = ([A-Za-z-]+) ":"  :=> header-value => pushToken("header-name", $1)
     * [main] version = ([A-Za-z-]+ "/" [.0-9]) => pushToken("version", $1)
     * [main] nl = "\x0D\x0A"
     * [header-value] header-value = [\x20-\x7E]* ( "\x0D\x0A" [\x09\x20]+ [\x20-\x7E]+ )* :=> main => pushToken("header-name")
     */
    public function read(ReaderBuffer $buffer): void
    {
        while ($buffer->valid()) {
            switch ($this->condition) {
                case HttpReader_Condition::MAIN:
                    switch ($this->state) {
                        case HttpReader_State::YY_START:
                            switch ($buffer->current()) {
                                // [A-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_VERSION_HEADER;
                                    $buffer->mark()->next();
                                    continue 4;
                                // [0-9]
                                case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:
                                case 0x39:
                                    $this->state = HttpReader_State::YY_CODE;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "/"
                                case 0x2F:
                                    $this->state = HttpReader_State::YY_PATH;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpReader_State::YY_HEADER_NAME;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "\r"
                                case 0x0D:
                                    $this->state = HttpReader_State::YY_NL;
                                    $buffer->mark()->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_CODE:
                            switch ($buffer->current()) {
                                // [0-9]
                                case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:
                                case 0x39:
                                    $buffer->next();
                                    continue 4;
                                // " "
                                case 0x20:
                                    $this->condition = HttpReader_Condition::STATUS;
                                    $this->state     = HttpReader_State::YY_START;
                                    $this->parser->pushToken("status-code", $buffer->getInt());
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_PATH:
                            $c = $buffer->current();
                            if ($c >= 0x21 && $c <= 0x7E && $c != 0x3F) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x3F) {
                                $this->parser->pushToken("path", $buffer->getString());
                                $this->state = HttpReader_State::YY_QUERY;
                                $buffer->next()->mark();
                                continue 3;
                            }
                            if ($c == 0x20) {
                                $this->parser->pushToken("path", $buffer->getString());
                                $this->state = HttpReader_State::YY_START;
                                $buffer->next();
                                continue 3;
                            }
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;

                        case HttpReader_State::YY_QUERY:
                            $c = $buffer->current();
                            if ($c >= 0x21 && $c <= 0x7E) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x20) {
                                $this->parser->pushToken("query", $buffer->getString());
                                $this->state = HttpReader_State::YY_START;
                                $buffer->next();
                                continue 3;
                            }
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;

                        case HttpReader_State::YY_METHOD_VERSION_HEADER:
                            switch ($buffer->current()) {
                                // [A-SU-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $buffer->next();
                                    continue 4;
                                // "/" version: HTTP/number
                                case 0x2F:
                                    if ($buffer->getString() !== "HTTP") {
                                        $this->reset($buffer);
                                        $this->activity->push(new ReaderException());
                                        return;
                                    }
                                    $this->state = HttpReader_State::YY_VERSION;
                                    $buffer->next()->mark();
                                    continue 4;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpReader_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // " "
                                case 0x20:
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("method", $buffer->getString());
                                    $buffer->next();
                                    continue 4;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpReader_Condition::HEADER_VALUE;
                                    $this->state     = HttpReader_State::YY_START;
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_VERSION:
                            switch ($buffer->current()) {
                                // [.0-9]
                                case 0x2E:
                                case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:case 0x39:
                                    $buffer->next();
                                    continue 4;
                                // " "
                                case 0x20:
                                    $this->parser->pushToken("version", $buffer->getFloat());
                                    $this->state = HttpReader_State::YY_START;
                                    $buffer->next();
                                    continue 4;
                                // "\r"
                                case 0x0D:
                                    $this->parser->pushToken("version", $buffer->getFloat());
                                    $this->state = HttpReader_State::YY_NL;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME:
                            switch ($buffer->current()) {
                                // [A-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $buffer->next();
                                    continue 4;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpReader_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // " "
                                case 0x20:
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("method", $buffer->getString());
                                    $buffer->next();
                                    continue 4;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpReader_Condition::HEADER_VALUE;
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_HEADER_NAME:
                            switch ($buffer->current()) {
                                // [A-Za-z-]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                case 0x2D:
                                    $buffer->next();
                                    continue 4;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpReader_Condition::HEADER_VALUE;
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }


                        case HttpReader_State::YY_NL:
                            switch ($buffer->current()) {
                                // "\n"
                                case 0x0A:
                                    $this->state = HttpReader_State::YY_DOUBLE_NL_1;
                                    $this->parser->pushToken("nl");
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_DOUBLE_NL_1:
                            switch ($buffer->current()) {
                                // "\r"
                                case 0x0D:
                                    $this->state = HttpReader_State::YY_DOUBLE_NL_2;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->state = HttpReader_State::YY_START;
                                    continue 4;
                            }

                        case HttpReader_State::YY_DOUBLE_NL_2:
                            switch ($buffer->current()) {
                                // "\n"
                                case 0x0A:
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("nl");
                                    $buffer->next()->mark();
                                    $this->parser->endOfTokens();
                                    return;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        default:
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;
                    }

                case HttpReader_Condition::STATUS:
                    switch ($this->state) {
                        case HttpReader_State::YY_START:
                            $this->state = HttpReader_State::YY_STATUS;
                            $buffer->mark();
                            // continue with next case
                        case HttpReader_State::YY_STATUS:
                            $c = $buffer->current();
                            if ($c >= 0x20 && $c <= 0xFE) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x0D) {
                                $this->parser->pushToken("status-text", $buffer->getString());
                                $this->condition = HttpReader_Condition::MAIN;
                                $this->state     = HttpReader_State::YY_NL;
                                $buffer->next();
                                continue 3;
                            }
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;
                    }

                case HttpReader_Condition::HEADER_VALUE:
                    switch ($this->state) {
                        case HttpReader_State::YY_START:
                            $this->state = HttpReader_State::YY_HEADER_SPACE;
                            // continue with next case
                        case HttpReader_State::YY_HEADER_SPACE:
                            switch ($buffer->current()) {
                                case 0x20:
                                    $buffer->next()->mark();
                                    continue 4;
                                case 0x0D:
                                    $this->state = HttpReader_State::YY_NL;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->state = HttpReader_State::YY_HEADER_VALUE;
                                    $buffer->mark();
                                    // continue with next case
                            }
                        case HttpReader_State::YY_HEADER_VALUE:
                            $c = $buffer->current();
                            if ($c >= 0x20 && $c <= 0xFE) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x0D) {
                                $this->state = HttpReader_State::YY_NL;
                                $buffer->next();
                                continue 3;
                            }
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;

                        case HttpReader_State::YY_NL:
                            switch ($buffer->current()) {
                                case 0x0A:
                                    $this->state = HttpReader_State::YY_INDENT;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_INDENT:
                            switch ($buffer->current()) {
                                case 0x09:
                                case 0x20:
                                    $this->state = HttpReader_State::YY_START;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $buffer->back(2);
                                    $this->condition = HttpReader_Condition::MAIN;
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("header-value", $buffer->getString());
                                    continue 4;
                            }
                    }
            }
        }

        if ($buffer->isLastChunk() === true) {
            $this->parser->endOfTokens();
        } else {
            $this->activity->repeat();
        }
    }
}
