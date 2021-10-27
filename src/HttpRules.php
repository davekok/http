<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\lalr1\attributes\{Rule,Nothing,Solution,Symbol,Symbols};
use davekok\lalr1\{Parser,SymbolType,Token};
use davekok\stream\Socket;
use davekok\stream\ReadyState;
use davekok\stream\Writer;
use davekok\stream\WriterBuffer;
use Psr\Log\LoggerInterface;

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
    private HttpReader $reader;

    public function __construct(
        private LoggerInterface $logger,
        private Parser $parser,
        private Socket $socket,
    ) {
        $this->parser->setRulesObject($this);
        $this->reader = new HttpReader($this->logger, $parser);
        $this->socket->setReader($this->reader);
        $this->socket->setReadyState(ReadyState::ReadReady);
    }

    #[Nothing]
    public function nothing(): void
    {
        $this->socket->setReadyState(ReadyState::Close);
    }

    #[Solution]
    public function solution(HttpRequest $request): void
    {
        var_dump($request);
        $this->socket->setReadyState(ReadyState::WriteReady);
        $this->socket->setWriter(new class($this->socket) implements Writer {
            public function __construct(private Socket $socket){}
            public function write(WriterBuffer $buffer): void
            {
                $buffer->add("HTTP/1.1 204 No Content\r\n\r\n");
                $this->socket->setReadyState(ReadyState::ReadReady);
            }
        });
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
