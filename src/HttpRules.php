<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\{Actionable,Cryptoble,Url};
use davekok\lalr1\attributes\{Rule,Symbol,Symbols};
use davekok\lalr1\{Parser,ParserException,Rules,SymbolType,Token};
use Throwable;

#[Symbols(
    new Symbol(SymbolType::ROOT,   "Message"),
    new Symbol(SymbolType::BRANCH, "RequestLine"),
    new Symbol(SymbolType::BRANCH, "ResponseLine"),
    new Symbol(SymbolType::LEAF,   "StatusCode"),
    new Symbol(SymbolType::LEAF,   "StatusText"),
    new Symbol(SymbolType::LEAF,   "Method"),
    new Symbol(SymbolType::LEAF,   "Path"),
    new Symbol(SymbolType::LEAF,   "Query"),
    new Symbol(SymbolType::LEAF,   "Version"),
    new Symbol(SymbolType::BRANCH, "Headers"),
    new Symbol(SymbolType::LEAF,   "HeaderKey"),
    new Symbol(SymbolType::LEAF,   "HeaderValue"),
    new Symbol(SymbolType::LEAF,   "NewLine"),
)]
class HttpRules implements Rules
{
    private readonly Parser $parser;

    public function __construct(
        private readonly Actionable $actionable,
    ) {}

    public function setParser(Parser $parser): void
    {
        $this->parser = $parser;
    }

    #[Rule("RequestLine Headers NewLine")]
    public function createRequest(array $tokens): Token
    {
        ["method" => $method, "path" => $path, "query" => $query, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        $cryptoEnabled = $this->actionable instanceof Cryptoble ? $this->actionable->isCryptoEnabled() : false;
        if (isset($headers["Host"])) {
            $hostport = explode(":", $headers["Host"]);
            [$host, $port] = match (count($hostport)) {
                2 => $hostport,
                1 => [...$hostport, $cryptoEnabled ? 443 : 80],
            };
            $port = (int)$port;
        }
        $url = new Url(
            scheme: $cryptoEnabled ? "https" : "http",
            host:   $host ?? null,
            port:   $port ?? null,
            path:   $path,
            query:  $query,
        );
        return $this->parser->createToken("Message", new HttpRequest($method, $url, $protocolVersion, $headers));
    }

    #[Rule("RequestLine NewLine")]
    public function createRequestNoHeaders(array $tokens): Token
    {
        ["method" => $method, "path" => $path, "query" => $query, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $cryptoEnabled = $this->actionable instanceof Cryptoble ? $this->actionable->isCryptoEnabled() : false;
        $url = new Url(
            scheme: $cryptoEnabled ? "https" : "http",
            host:   $host ?? null,
            port:   $port ?? null,
            path:   $path,
            query:  $query,
        );
        return $this->parser->createToken("Message", new HttpRequest($method, $url, $protocolVersion));
    }

    #[Rule("ResponseLine Headers NewLine")]
    public function createResponse(array $tokens): Token
    {
        ["status" => $status, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        return $this->parser->createToken("Message", new HttpResponse($status, $protocolVersion, $headers));
    }

    #[Rule("ResponseLine NewLine")]
    public function createResponseNoHeaders(array $tokens): Token
    {
        ["status" => $status, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        return $this->parser->createToken("Message", new HttpResponse($status, $protocolVersion));
    }

    #[Rule("Method Path Query Version NewLine")]
    public function createRequestLineWithQuery(array $tokens): Token
    {
        return $this->parser->createToken("RequestLine", [
            "method" => $tokens[0]->value,
            "path" => $tokens[1]->value,
            "query" => $tokens[2]->value,
            "protocolVersion" => $tokens[3]->value,
        ]);
    }

    #[Rule("Method Path Version NewLine")]
    public function createRequestLine(array $tokens): Token
    {
        return $this->parser->createToken("RequestLine", [
            "method" => $tokens[0]->value,
            "path" => $tokens[1]->value,
            "query" => null,
            "protocolVersion" => $tokens[2]->value,
        ]);
    }

    #[Rule("Version StatusCode StatusText NewLine")]
    public function createResponseLine(array $tokens): Token
    {
        $status = HttpStatus::tryFrom($tokens[1]->value);
        if ($status === null || $status->text() !== $tokens[2]->value) {
            throw new ParserException("Unknown status code or text.");
        }
        return $this->parser->createToken("ResponseLine", [
            "status"          => $status,
            "protocolVersion" => $tokens[0]->value,
        ]);
    }

    #[Rule("HeaderKey HeaderValue NewLine")]
    public function createHeaders(array $tokens): Token
    {
        return $this->parser->createToken("Headers", [$tokens[0]->value => $tokens[1]->value]);
    }

    #[Rule("Headers HeaderKey HeaderValue NewLine")]
    public function addHeader(array $tokens): Token {
        $tokens[0]->value[$tokens[1]->value] = $tokens[2]->value;
        return $tokens[0];
    }
}
