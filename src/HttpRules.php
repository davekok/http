<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\attributes\{Rule,Solution,Symbol,Symbols};
use davekok\lalr1\{Parser,ParserException,SymbolType,Token};
use davekok\stream\Activity;
use davekok\stream\ReadyState;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * TODO: support responses.
 */
#[Symbols(
    new Symbol(SymbolType::ROOT, "request"),
    new Symbol(SymbolType::BRANCH, "request-line"),
    new Symbol(SymbolType::LEAF, "method"),
    new Symbol(SymbolType::LEAF, "path"),
    new Symbol(SymbolType::LEAF, "version"),
    new Symbol(SymbolType::BRANCH, "headers"),
    new Symbol(SymbolType::LEAF, "header-name"),
    new Symbol(SymbolType::LEAF, "header-value"),
    new Symbol(SymbolType::LEAF, "nl"),
)]
class HttpRules
{
    public function __construct(
        private Parser $parser,
        private Activity $activity,
    ) {}

    #[Solution]
    public function solution(HttpMessage|ParserException $message): void
    {
        $this->activity->push($message);
    }

    #[Rule("request-line headers nl")]
    public function reduceRequest(array $tokens): Token
    {
        ["method" => $method, "path" => $path, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        return $this->parser->createToken("request", new HttpRequest($method, $path, $protocolVersion, $headers));
    }

    #[Rule("method path version nl")]
    public function reduceRequestLine(array $tokens): Token
    {
        return $this->parser->createToken("request-line", [
            "method" => $tokens[0]->value,
            "path" => $tokens[1]->value,
            "protocolVersion" => $tokens[2]->value,
        ]);
    }

    #[Rule("header-name header-value nl")]
    public function startHeaders(array $tokens): Token
    {
        return $this->parser->createToken("headers", [$tokens[0]->value => $tokens[1]->value]);
    }

    #[Rule("headers header-name header-value nl")]
    public function addHeader(array $tokens): Token {
        $tokens[0]->value[$tokens[1]->value] = $tokens[2]->value;
        return $tokens[0];
    }
}
