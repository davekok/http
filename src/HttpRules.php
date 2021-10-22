<?php

namespace davekok\http;

use davekok\lalr1\Parser;
use davekok\lalr1\Rule;
use davekok\lalr1\Symbol;
use davekok\lalr1\Symbols;
use davekok\lalr1\SymbolType;
use davekok\lalr1\Token;

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
    private HttpScanner $scanner;

    public function __construct(
        private Parser $parser,
        private Connection $connection,
    ) {
        $this->parser->setRulesObject($this);
        $this->scanner = new HttpScanner($parser);
        $this->connection->pushScanner($this->scanner);
    }

    #[Solution]
    public function reduceRequest(array $tokens): Token
    {
    }

    #[Rule("request-line headers nl")]
    public function reduceRequest(array $tokens): Token
    {
        ["method" => $method, "path" => $path, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        return $this->parser->createToken("request", new HttpRequest($method, $path, $protocolVersion, $headers));
    }

    #[Rule("method path version nl")]
    public function reduceRequestLine(array tokens): Token
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
    public function addHeader(Token[] $tokens): Token {
        $tokens[0]->value[$tokens[1]->value] = $tokens[2]->value;
        return $tokens[0];
    }
}
