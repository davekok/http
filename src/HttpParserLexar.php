<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;

enum HttpParserLexar_Condition
{
    case MAIN;
    case STATUS;
    case HEADER;
}

enum HttpParserLexar_State
{
    case YY_START;
    case YY_CODE;
    case YY_STATUS;
    case YY_METHOD_OR_HEADER_NAME;
    case YY_METHOD_PROTOCOL_HEADER;
    case YY_PROTOCOL;
    case YY_PATH;
    case YY_QUERY;
    case YY_HEADER_KEY;
    case YY_HEADER_SPACE;
    case YY_HEADER_VALUE;
    case YY_INDENT;
    case YY_NL;
    case YY_DOUBLE_NL_1;
    case YY_DOUBLE_NL_2;
}

/**
 * Lexical analyzer
 */
trait HttpParserLexar
{
    private HttpParserLexar_Condition $condition = HttpParserLexar_Condition::MAIN;
    private HttpParserLexar_State     $state     = HttpParserLexar_State::YY_START;

    private iterable $input;
    private int $mark;
    private int $index;
    private int $length;
    private string $chunk;
    private string $leftover = "";

    /**
     * Scan for HTTP tokens.
     *
     * Message      := RequestLine Headers NewLine
     * Message      := ResponseLine Headers NewLine
     * RequestLine  := Method Path Query Protocol NewLine
     * RequestLine  := Method Path Protocol NewLine
     * ResponseLine := Protocol StatusCode StatusText NewLine
     * Headers      := HeaderKey HeaderValue NewLine
     * Headers      := Headers HeaderKey HeaderValue NewLine
     *
     * [main]        protocol = ([A-Za-z-]+ "/" [.0-9])                     => Protocol($1)
     * [main]        code     = ([0-9]+) " "                   :=> status   => StatusCode($1)
     * [status]      status   = ( text+ )                      :=> main     => StatusText($1)
     * [main]        method   = ([A-Za-z]+) " "                             => Method($1)
     * [main]        path     = ("/" [\x21-\x7E]+) " "                      => Path($1)
     * [main]        key      = ([A-Za-z-]+) ":"               :=> header   => HeaderKey($1)
     * [main]        newline  = nl                                          => NewLine()
     * [header]      value    = ( text* ( nl space+ text+ )* ) :=> main     => HeaderValue($1)
     *
     * [main,header]   nl     = "\x0D\x0A"
     * [status,header] text   = [\x20-\x7E]
     * [header]        space  = [\x09\x20]
     */
    public function lex(iterable $input): Generator
    {
        $this->input = $input;
        foreach ($this->input as $this->chunk) {
            $this->chunk = $this->leftover.$this->chunk;
            $this->mark = 0;
            $this->index = 0;
            $this->length = strlen($this->chunk);
            while ($this->index < $this->length) {
                switch ($this->condition) {
                    case HttpParserLexar_Condition::MAIN:
                        switch ($this->state) {
                            case HttpParserLexar_State::YY_START:
                                switch (ord($this->chunk[$this->index])) {
                                    // [A-Za-z]
                                    case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                    case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                    case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                    case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                    case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                    case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                        $this->state = HttpParserLexar_State::YY_METHOD_PROTOCOL_HEADER;
                                        $this->mark = $this->index++;
                                        continue 4;
                                    // [0-9]
                                    case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:
                                    case 0x39:
                                        $this->state = HttpParserLexar_State::YY_CODE;
                                        $this->mark = $this->index++;
                                        continue 4;
                                    // "/"
                                    case 0x2F:
                                        $this->state = HttpParserLexar_State::YY_PATH;
                                        $this->mark = $this->index++;
                                        continue 4;
                                    // "-"
                                    case 0x2D:
                                        $this->state = HttpParserLexar_State::YY_HEADER_KEY;
                                        $this->mark = $this->index++;
                                        continue 4;
                                    // "\r"
                                    case 0x0D:
                                        $this->state = HttpParserLexar_State::YY_NL;
                                        $this->mark = $this->index++;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_CODE:
                                switch (ord($this->chunk[$this->index])) {
                                    // [0-9]
                                    case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:
                                    case 0x39:
                                        ++$this->index;
                                        continue 4;
                                    // " "
                                    case 0x20:
                                        $this->condition = HttpParserLexar_Condition::STATUS;
                                        $this->state     = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::StatusCode, (int)$this->getString());
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_PATH:
                                $c = ord($this->chunk[$this->index]);
                                if ($c >= 0x21 && $c <= 0x7E && $c != 0x3F) {
                                    ++$this->index;
                                    continue 3;
                                }
                                if ($c == 0x3F) {
                                    yield new HttpParserToken(HttpParserType::Path, $this->getString());
                                    $this->state = HttpParserLexar_State::YY_QUERY;
                                    $this->mark = ++$this->index;
                                    continue 3;
                                }
                                if ($c == 0x20) {
                                    yield new HttpParserToken(HttpParserType::Path, $this->getString());
                                    $this->state = HttpParserLexar_State::YY_START;
                                    ++$this->index;
                                    continue 3;
                                }
                                throw new HttpParserException();

                            case HttpParserLexar_State::YY_QUERY:
                                $c = ord($this->chunk[$this->index]);
                                if ($c >= 0x21 && $c <= 0x7E) {
                                    ++$this->index;
                                    continue 3;
                                }
                                if ($c == 0x20) {
                                    parse_str($this->getString(), $query);
                                    yield new HttpParserToken(HttpParserType::Query, $query);
                                    $this->state = HttpParserLexar_State::YY_START;
                                    ++$this->index;
                                    continue 3;
                                }
                                throw new HttpParserException();

                            case HttpParserLexar_State::YY_METHOD_PROTOCOL_HEADER:
                                switch (ord($this->chunk[$this->index])) {
                                    // [A-SU-Za-z]
                                    case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                    case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                    case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                    case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                    case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                    case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                        ++$this->index;
                                        continue 4;
                                    // "/" version: HTTP/number
                                    case 0x2F:
                                        $this->state = HttpParserLexar_State::YY_PROTOCOL;
                                        ++$this->index;
                                        continue 4;
                                    // "-"
                                    case 0x2D:
                                        $this->state = HttpParserLexar_State::YY_HEADER_KEY;
                                        ++$this->index;
                                        continue 4;
                                    // " "
                                    case 0x20:
                                        $this->state = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::Method, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    // ":"
                                    case 0x3A:
                                        $this->condition = HttpParserLexar_Condition::HEADER;
                                        $this->state     = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::HeaderKey, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_PROTOCOL:
                                switch (ord($this->chunk[$this->index])) {
                                    // [.0-9]
                                    case 0x2E:
                                    case 0x30:case 0x31:case 0x32:case 0x33:case 0x34:case 0x35:case 0x36:case 0x37:case 0x38:case 0x39:
                                        ++$this->index;
                                        continue 4;
                                    // " "
                                    case 0x20:
                                        yield new HttpParserToken(HttpParserType::Protocol, $this->getString());
                                        $this->state = HttpParserLexar_State::YY_START;
                                        ++$this->index;
                                        continue 4;
                                    // "\r"
                                    case 0x0D:
                                        yield new HttpParserToken(HttpParserType::Protocol, $this->getString());
                                        $this->state = HttpParserLexar_State::YY_NL;
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_METHOD_OR_HEADER_NAME:
                                switch (ord($this->chunk[$this->index])) {
                                    // [A-Za-z]
                                    case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                    case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                    case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                    case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                    case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                    case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                        ++$this->index;
                                        continue 4;
                                    // "-"
                                    case 0x2D:
                                        $this->state = HttpParserLexar_State::YY_HEADER_KEY;
                                        ++$this->index;
                                        continue 4;
                                    // " "
                                    case 0x20:
                                        $this->state = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::Method, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    // ":"
                                    case 0x3A:
                                        $this->condition = HttpParserLexar_Condition::HEADER;
                                        $this->state     = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::HeaderKey, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_HEADER_KEY:
                                switch (ord($this->chunk[$this->index])) {
                                    // [A-Za-z-]
                                    case 0x41:case 0x42:case 0x43:case 0x44:case 0x45:case 0x46:case 0x47:case 0x48:case 0x49:
                                    case 0x4A:case 0x4B:case 0x4C:case 0x4D:case 0x4E:case 0x4F:case 0x50:case 0x51:case 0x52:
                                    case 0x53:case 0x54:case 0x55:case 0x56:case 0x57:case 0x58:case 0x59:case 0x5A:
                                    case 0x61:case 0x62:case 0x63:case 0x64:case 0x65:case 0x66:case 0x67:case 0x68:case 0x69:
                                    case 0x6A:case 0x6B:case 0x6C:case 0x6D:case 0x6E:case 0x6F:case 0x70:case 0x71:case 0x72:
                                    case 0x73:case 0x74:case 0x75:case 0x76:case 0x77:case 0x78:case 0x79:case 0x7A:
                                    case 0x2D:
                                        ++$this->index;
                                        continue 4;
                                    // ":"
                                    case 0x3A:
                                        $this->condition = HttpParserLexar_Condition::HEADER;
                                        $this->state     = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::HeaderKey, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }


                            case HttpParserLexar_State::YY_NL:
                                switch (ord($this->chunk[$this->index])) {
                                    // "\n"
                                    case 0x0A:
                                        $this->state = HttpParserLexar_State::YY_DOUBLE_NL_1;
                                        yield new HttpParserToken(HttpParserType::NewLine, $this->getString());
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_DOUBLE_NL_1:
                                switch (ord($this->chunk[$this->index])) {
                                    // "\r"
                                    case 0x0D:
                                        $this->state = HttpParserLexar_State::YY_DOUBLE_NL_2;
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        $this->state = HttpParserLexar_State::YY_START;
                                        continue 4;
                                }

                            case HttpParserLexar_State::YY_DOUBLE_NL_2:
                                switch (ord($this->chunk[$this->index])) {
                                    // "\n"
                                    case 0x0A:
                                        $this->state = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::NewLine, $this->getString());
                                        $this->mark = ++$this->index;
                                        yield null;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            default:
                                throw new HttpParserException();
                        }

                    case HttpParserLexar_Condition::STATUS:
                        switch ($this->state) {
                            case HttpParserLexar_State::YY_START:
                                $this->state = HttpParserLexar_State::YY_STATUS;
                                $this->mark = $this->index;
                                // continue with next case
                            case HttpParserLexar_State::YY_STATUS:
                                $c = ord($this->chunk[$this->index]);
                                if ($c >= 0x20 && $c <= 0xFE) {
                                    ++$this->index;
                                    continue 3;
                                }
                                if ($c == 0x0D) {
                                    yield new HttpParserToken(HttpParserType::StatusText, $this->getString());
                                    $this->condition = HttpParserLexar_Condition::MAIN;
                                    $this->state     = HttpParserLexar_State::YY_NL;
                                    ++$this->index;
                                    continue 3;
                                }
                                // continue with next case
                            default:
                                throw new HttpParserException();
                        }

                    case HttpParserLexar_Condition::HEADER:
                        switch ($this->state) {
                            case HttpParserLexar_State::YY_START:
                                $this->state = HttpParserLexar_State::YY_HEADER_SPACE;
                                // continue with next case
                            case HttpParserLexar_State::YY_HEADER_SPACE:
                                switch (ord($this->chunk[$this->index])) {
                                    case 0x20:
                                        $this->mark = ++$this->index;
                                        continue 4;
                                    case 0x0D:
                                        $this->state = HttpParserLexar_State::YY_NL;
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        $this->state = HttpParserLexar_State::YY_HEADER_VALUE;
                                        $this->mark = $this->index;
                                        // continue with next case
                                }

                            case HttpParserLexar_State::YY_HEADER_VALUE:
                                $c = ord($this->chunk[$this->index]);
                                if ($c >= 0x20 && $c <= 0xFE) {
                                    ++$this->index;
                                    continue 3;
                                }
                                if ($c == 0x0D) {
                                    $this->state = HttpParserLexar_State::YY_NL;
                                    ++$this->index;
                                    continue 3;
                                }
                                throw new HttpParserException();

                            case HttpParserLexar_State::YY_NL:
                                switch (ord($this->chunk[$this->index])) {
                                    case 0x0A:
                                        $this->state = HttpParserLexar_State::YY_INDENT;
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        throw new HttpParserException();
                                }

                            case HttpParserLexar_State::YY_INDENT:
                                switch (ord($this->chunk[$this->index])) {
                                    case 0x09:
                                    case 0x20:
                                        $this->state = HttpParserLexar_State::YY_START;
                                        ++$this->index;
                                        continue 4;
                                    default:
                                        --$this->index;
                                        --$this->index;
                                        $this->condition = HttpParserLexar_Condition::MAIN;
                                        $this->state = HttpParserLexar_State::YY_START;
                                        yield new HttpParserToken(HttpParserType::HeaderValue, $this->getString());
                                        continue 4;
                                }
                            default:
                                throw new HttpParserException();
                        }
                }
            }
            $this->leftover = substr($this->chunk, $this->mark);
        }
        yield null;
    }

    private function getString(): string
    {
        return substr($this->chunk, $this->mark, $this->index - $this->mark);
    }

    public function getBody(int $contentLength): Generator
    {
        $readSoFar = 0;
        if ($this->mark < $this->length) {
            $readSoFar = $this->length - $this->mark;
            yield substr($this->chunk, $this->mark);
        }
        foreach ($this->input as $this->chunk) {
            $this->length = strlen($this->chunk);
            if (($readSoFar + $this->length) > $contentLength) {
                $l = $contentLength - $readSoFar;
                $chunk = substr($this->chunk, 0, $l);
                $this->chunk = substr($this->chunk, $l);
                $this->mark = 0;
                $this->index = 0;
                $this->length = strlen($this->chunk);
                yield $chunk;
                return;
            } else if (($readSoFar + $this->length) === $contentLength) {
                $chunk = $this->chunk;
                foreach ($this->input as $this->chunk) {
                    $this->mark = 0;
                    $this->index = 0;
                    $this->length = strlen($this->chunk);
                    yield $chunk;
                    return;
                }
            }
            $readSoFar += $this->length;
            yield $this->chunk;
        }
    }
}
