<?php

declare(strict_types=1);

namespace davekok\http;

use Generator;

class HttpFormatter
{
    public function format(iterable $messages): Generator
    {
        foreach ($messages as $message) {
            yield match (true) {
                $message instanceof HttpRequest => $message->method
                    . " "
                    . $message->path
                    . "?"
                    . http_build_query($message->query, encoding_type: PHP_QUERY_RFC3986)
                    . " "
                    . $message->protocol
                    . "\r\n",

                $message instanceof HttpResponse => $message->protocol
                    . " "
                    . $message->status->code()
                    . " "
                    . $message->status->text()
                    . "\r\n",

                default => throw new HttpFormatterException("Invalid http message"),
            };
            if (isset($message->headers)) {
                foreach ($message->headers as $key => $value) {
                    $key = HttpMessage::camelCaseToSnakeCase($key);
                    yield "$key:$value\r\n";
                }
            }
            yield "\r\n";
            if (isset($message->body)) {
                yield from $message->body;
            }
        }
    }
}
