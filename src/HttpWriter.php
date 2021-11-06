<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\stream\Activity;
use davekok\stream\ReadyState;
use davekok\stream\Writer;
use davekok\stream\WriterBuffer;
use davekok\stream\WriterException;
use Psr\Log\LoggerInterface;

class HttpWriter implements Writer
{
    private HttpMessage $message;

    public function __construct(private Activity $activity) {}

    public function send(HttpMessage $message): void
    {
        $this->message = $message;
        $this->activity->andThenWrite($this);
    }

    public function write(WriterBuffer $buffer): void
    {
        if ($this->message instanceof HttpRequest) {
            $buffer->add("{$this->message->method} {$this->message->path} HTTP/{$this->message->protocolVersion}\r\n");
        } else {
            $buffer->add("HTTP/{$this->message->protocolVersion} {$this->message->status->code()} {$this->message->status->text()}\r\n");
        }
        foreach ($this->message->headers as $name => $value) {
            $buffer->add("$name:$value\r\n");
        }
        if ($this->message->body !== null) {
            $buffer->add("Content-Length: ".strlen($this->message->body)."\r\n");
        }
        $buffer->add("\r\n");
        if ($this->message->body !== null) {
            $buffer->add($this->message->body);
        }
    }
}
