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
    case HEADER_VALUE;
}

enum HttpReader_State
{
    case YY_START;
    case YY_METHOD_OR_HEADER_NAME;
    case YY_METHOD_OR_HEADER_NAME_OR_VERSION_1;
    case YY_METHOD_OR_HEADER_NAME_OR_VERSION_2;
    case YY_METHOD_OR_HEADER_NAME_OR_VERSION_3;
    case YY_METHOD_OR_HEADER_NAME_OR_VERSION_4;
    case YY_VERSION_5;
    case YY_VERSION_6;
    case YY_VERSION_7;
    case YY_PATH;
    case YY_HEADER_NAME;
    case YY_HEADER_SPACE;
    case YY_HEADER_VALUE;
    case YY_INDENT;
    case YY_NL;
    case YY_DOUBLE_NL_1;
    case YY_DOUBLE_NL_2;
}

enum HttpReader_TokenType
{
    case T_NL;
    case T_METHOD;
    case T_PATH;
    case T_VERSION_1_0;
    case T_VERSION_1_1;
    case T_HEADER_NAME;
    case T_HEADER_VALUE;
}

/**
 * TODO: support responses.
 */
class HttpReader implements Reader
{
    public function __construct(
        private Activity $activity,
        private Parser $parser,
        private HttpRules $rules,
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
     * [main] method = ([A-Za-z]+) " " => pushToken(T_METHOD, $1)
     * [main] path = ("/" [\x21-\x7E]+) " " => pushToken(T_PATH, $1)
     * [main] header-name = ([A-Za-z-]+) ":"  :=> header-value => pushToken(T_HEADER_NAME, $1)
     * [main] version = ("HTTP/1." [10]) => pushToken(T_VERSION, $1)
     * [main] nl = "\x0D\x0A" => pushToken(T_VERSION)
     * [header-value] header-value = [\x20-\x7E]* ( "\x0D\x0A" [\x09\x20]+ [\x20-\x7E]+ )* :=> main => pushToken(T_HEADER_VALUE)
     */
    public function read(ReaderBuffer $buffer): void
    {
        while ($buffer->valid()) {
            switch ($this->condition) {
                case HttpReader_Condition::MAIN:
                    switch ($this->state) {
                        case HttpReader_State::YY_START:
                            switch ($buffer->peek()) {
                                // [A-GI-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:          case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "H"
                                case 0x48:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_1;
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

                        case HttpReader_State::YY_PATH:
                            $c = $buffer->peek();
                            if ($c >= 0x21 && $c <= 0x7E) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x20) {
                                $this->state = HttpReader_State::YY_START;
                                $this->parser->pushToken("path", $buffer->getString());
                                $buffer->next();
                                continue 3;
                            }
                            $this->reset($buffer);
                            $this->activity->push(new ReaderException());
                            return;

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_1:
                            switch ($buffer->peek()) {
                                // [A-SU-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:          case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // "T"
                                case 0x54:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_2;
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

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_2:
                            switch ($buffer->peek()) {
                                // [A-SU-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:          case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // "T"
                                case 0x54:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_3;
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

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_3:
                            switch ($buffer->peek()) {
                                // [A-OQ-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:          case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // "P"
                                case 0x50:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_4;
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

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_4:
                            switch ($buffer->peek()) {
                                // [A-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpReader_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue 4;
                                // "/"
                                case 0x2F:
                                    $this->state = HttpReader_State::YY_VERSION_5;
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

                        case HttpReader_State::YY_VERSION_5:
                            switch ($buffer->peek()) {
                                // "1"
                                case 0x31:
                                    $this->state = HttpReader_State::YY_VERSION_6;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_VERSION_6:
                            switch ($buffer->peek()) {
                                // "."
                                case 0x2E:
                                    $this->state = HttpReader_State::YY_VERSION_7;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_VERSION_7:
                            switch ($buffer->peek()) {
                                // "0"
                                case 0x30:
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("version", 1.0);
                                    $buffer->next();
                                    continue 4;
                                // "1"
                                case 0x31:
                                    $this->state = HttpReader_State::YY_START;
                                    $this->parser->pushToken("version", 1.1);
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $this->reset($buffer);
                                    $this->activity->push(new ReaderException());
                                    return;
                            }

                        case HttpReader_State::YY_METHOD_OR_HEADER_NAME:
                            switch ($buffer->peek()) {
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
                            switch ($buffer->peek()) {
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
                            switch ($buffer->peek()) {
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
                            switch ($buffer->peek()) {
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
                            switch ($buffer->peek()) {
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

                case HttpReader_Condition::HEADER_VALUE:
                    switch ($this->state) {
                        case HttpReader_State::YY_START:
                            $this->state = HttpReader_State::YY_HEADER_SPACE;
                            // continue with next case
                        case HttpReader_State::YY_HEADER_SPACE:
                            switch ($buffer->peek()) {
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
                            $c = $buffer->peek();
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
                            switch ($buffer->peek()) {
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
                            switch ($buffer->peek()) {
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
