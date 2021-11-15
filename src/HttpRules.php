<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\attributes\{Rule,Solution,Symbol,Symbols};
use davekok\lalr1\{Parser,ParserException,SymbolType,Token};
use davekok\stream\{Activity,Url};
use Throwable;

#[Symbols(
    new Symbol(SymbolType::ROOT, "message"),
    new Symbol(SymbolType::BRANCH, "request-line"),
    new Symbol(SymbolType::BRANCH, "response-line"),
    new Symbol(SymbolType::LEAF, "status-code"),
    new Symbol(SymbolType::LEAF, "status-text"),
    new Symbol(SymbolType::LEAF, "method"),
    new Symbol(SymbolType::LEAF, "path"),
    new Symbol(SymbolType::LEAF, "query"),
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
        ["method" => $method, "path" => $path, "query" => $query, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        $info    = $this->activity->getStreamInfo();
        if (isset($headers["Host"])) {
            $hostport = explode(":", $headers["Host"]);
            [$host, $port] = match (count($hostport)) {
                2 => $hostport,
                1 => [$hostport, $info->cryptoEnabled ? 443 : 80],
            };
            $port = (int)$port;
        } else {
            $host = null;
            $port = null;
        }
        $url = new Url(
            scheme: $info->cryptoEnabled ? "https" : "http",
            host:   $host,
            port:   $port,
            path:   $path,
            query:  $query,
        );
        return $this->parser->createToken("message", new HttpRequest($method, $url, $protocolVersion, $headers));
    }

    #[Rule("response-line headers nl")]
    public function reduceResponse(array $tokens): Token
    {
        ["status" => $status, "protocolVersion" => $protocolVersion] = $tokens[0]->value;
        $headers = $tokens[1]->value;
        return $this->parser->createToken("message", new HttpResponse($status, $protocolVersion, $headers));
    }

    #[Rule("method path query version nl")]
    public function reduceRequestLineWithQuery(array $tokens): Token
    {
        return $this->parser->createToken("request-line", [
            "method" => $tokens[0]->value,
            "path" => $tokens[1]->value,
            "query" => $tokens[1]->value,
            "protocolVersion" => $tokens[2]->value,
        ]);
    }

    #[Rule("method path version nl")]
    public function reduceRequestLine(array $tokens): Token
    {
        return $this->parser->createToken("request-line", [
            "method" => $tokens[0]->value,
            "path" => $tokens[1]->value,
            "query" => null,
            "protocolVersion" => $tokens[2]->value,
        ]);
    }

    #[Rule("version status-code status-text nl")]
    public function reduceResponseLine(array $tokens): Token
    {
        $status = Status::tryFrom($tokens[1]->value);
        if ($status === null || $status->text() !== $tokens[2]->value) {
            $parserException = new ParserException("Unknown status code or text.");
            $this->activity->addClose()->push($parserException);
            throw $parserException;
        }
        return $this->parser->createToken("response-line", [
            "status"          => $status,
            "protocolVersion" => $tokens[0]->value,
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
