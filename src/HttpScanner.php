<?php

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\stream\ScanBuffer;
use davekok\stream\ScanException;
use davekok\stream\Scanner;

enum HttpScanner_Condition
{
    case MAIN;
    case HEADER_VALUE;
}

enum HttpScanner_State
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
    case YY_HEADER_VALUE;
    case YY_INDENT;
    case YY_NL;
    case YY_DOUBLE_NL_1;
    case YY_DOUBLE_NL_2;
}

enum HttpScanner_TokenType
{
    case T_NL;
    case T_METHOD;
    case T_PATH;
    case T_VERSION_1_0;
    case T_VERSION_1_1;
    case T_HEADER_NAME;
    case T_HEADER_VALUE;
}

class HttpScanner implements Scanner {
    public function __construct(
        private readonly Parser $parser,
        private HttpScanner_Condition $condition = HttpScanner_Condition::MAIN,
        private HttpScanner_State $state = HttpScanner_Condition::YYSTART,
    ) {
        $this->parser->setRulesObject($this);
    }

    public function reset(): void
    {
        $this->condition = HttpScanner_Condition::MAIN;
        $this->state = HttpScanner_Condition::YYSTART;
    }

    public function endOfInput(ScanBuffer $buffer): void
    {
        $this->parser->endOfTokens();
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
    public function scan(ScanBuffer $buffer): void {
        while ($buffer->valid()) {
            switch ($this->condition) {
                case HttpScanner_Condition::MAIN:
                    switch ($this->state) {
                        case HttpScanner_State::YY_START:
                            switch ($buffer->peek()) {
                                // [A-GI-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:          case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "H"
                                case 0x48:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_1;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "/"
                                case 0x2F:
                                    $this->state = HttpScanner_State::YY_PATH;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->mark()->next();
                                    continue 4;
                                // "\r"
                                case 0x0D:
                                    $this->state = HttpScanner_State::YY_NL;
                                    $buffer->mark()->next();
                                    continue 4;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_PATH:
                            $c = $buffer->peek();
                            if ($c >= 0x21 && $c <= 0x7E) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x20) {
                                $this->state = HttpScanner_State::YY_START;
                                $buffer->next();
                                $this->parser->pushToken("path", $buffer->getString());
                                continue 3;
                            }
                            throw new ScanException();

                        case HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_1:
                            switch ($buffer->peek()) {
                                // [A-SU-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:          case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // "T"
                                case 0x54:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_2;
                                    $buffer->next();
                                    continue;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // " "
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("method", $buffer->getString());
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_2:
                            switch ($buffer->peek()) {
                                // [A-SU-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:          case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // "T"
                                case 0x54:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_3;
                                    $buffer->next();
                                    continue;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // " "
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("method", $buffer->getString());
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_3:
                            switch ($buffer->peek()) {
                                // [A-OQ-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:          case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // "P"
                                case 0x50:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_4;
                                    $buffer->next();
                                    continue;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // " "
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("method", $buffer->getString());
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_METHOD_OR_HEADER_NAME_OR_VERSION_4:
                            switch ($buffer->peek()) {
                                // [A-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $this->state = HttpScanner_State::YY_METHOD_OR_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // "/"
                                case 0x2F:
                                    $this->state = HttpScanner_State::YY_VERSION_5;
                                    $buffer->next();
                                    continue;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // " "
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("method", $buffer->getString());
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_VERSION_5:
                            switch ($buffer->peek()) {
                                // "1"
                                case 0x31:
                                    $this->state = HttpScanner_State::YY_VERSION_6;
                                    $buffer->next();
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_VERSION_6:
                            switch ($buffer->peek()) {
                                // "."
                                case 0x2E:
                                    $this->state = HttpScanner_State::YY_VERSION_7;
                                    $buffer->next();
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_VERSION_7:
                            switch ($buffer->peek()) {
                                // "0"
                                case 0x30:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("version", 1.0);
                                    continue;
                                // "1"
                                case 0x31:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("version", 1.1);
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_METHOD_OR_HEADER_NAME:
                            switch ($buffer->peek()) {
                                // [A-Za-z]
                                case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    $buffer->next();
                                    continue;
                                // "-"
                                case 0x2D:
                                    $this->state = HttpScanner_State::YY_HEADER_NAME;
                                    $buffer->next();
                                    continue;
                                // " "
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("method", $buffer->getString());
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_HEADER_NAME:
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
                                    continue;
                                // ":"
                                case 0x3A:
                                    $this->condition = HttpScanner_Condition::HEADER_VALUE;
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    $this->parser->pushToken("header-name", $buffer->getString());
                                    continue;
                                default:
                                    throw new ScanException();
                            }


                        case HttpScanner_State::YY_NL:
                            switch ($buffer->peek()) {
                                // "\n"
                                case 0x0A:
                                    $this->state = HttpScanner_State::YY_DOUBLE_NL_1;
                                    $buffer->next();
                                    $this->parser->pushToken("nl");
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_DOUBLE_NL_1:
                            switch ($buffer->peek()) {
                                // "\r"
                                case 0x0D:
                                    $this->state = HttpScanner_State::YY_DOUBLE_NL_2;
                                    $buffer->next();
                                    continue;
                                default:
                                    $this->state = HttpScanner_State::YY_START;
                                    continue;
                            }

                        case HttpScanner_State::YY_DOUBLE_NL_2:
                            switch ($buffer->peek()) {
                                // "\n"
                                case 0x0A:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next()->mark();
                                    $this->parser->pushToken("nl");
                                    $this->parser->endOfTokens();
                                    continue;
                                default:
                                    throw new ScanException();
                            }

                        default:
                            throw new ScanException();
                    }

                case HttpScanner_Condition::HEADER_VALUE:
                    switch ($this->state) {
                        case HttpScanner_State::YY_START:
                            $this->state = HttpScanner_State::YY_HEADER_VALUE;
                            $buffer->mark();
                            // continue with next case
                        case HttpScanner_State::YY_HEADER_VALUE:
                            $c = $buffer->peek();
                            if ($c >= 0x20 && $c <= 0xFE) {
                                $buffer->next();
                                continue 3;
                            }
                            if ($c == 0x0D) {
                                $this->state = HttpScanner_State::YY_NL;
                                $buffer->next();
                                continue 3;
                            }
                            throw new ScanException();

                        case HttpScanner_State::YY_NL:
                            switch ($buffer->peek()) {
                                case 0x0A:
                                    $this->state = HttpScanner_State::YY_INDENT;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    throw new ScanException();
                            }

                        case HttpScanner_State::YY_INDENT:
                            switch ($buffer->peek()) {
                                case 0x09:
                                case 0x20:
                                    $this->state = HttpScanner_State::YY_START;
                                    $buffer->next();
                                    continue 4;
                                default:
                                    $buffer->back();
                                    $buffer->back();
                                    $this->condition = HttpScanner_Condition::MAIN;
                                    $this->state = HttpScanner_State::YY_START;
                                    $this->parser->pushToken("header-value", $buffer->getString());
                                    continue 4;
                            }
                    }
            }
        }
    }
}
