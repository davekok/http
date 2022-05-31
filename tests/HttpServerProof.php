<?php

declare(strict_types=1);

namespace davekok\http\tests;

use davekok\http\SimpleStreamServer;
use davekok\http\HttpServer;
use davekok\http\HttpRequestHandler;
use davekok\http\HttpRequest;
use davekok\http\HttpResponse;

include(__DIR__ . "/../vendor/autoload.php");

class AppRequestHandler implements HttpRequestHandler
{
    public function handleRequest(HttpRequest $request): HttpResponse
    {
        return HttpResponse::html(<<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Test Page</title>
                </head>
                <body>
                    <header>
                        <h1>Test page</h1>
                    </header>
                    <main>
                        <p>My awesome test page.</p>
                    </main>
                </body>
            </html>
            HTML
        );
    }
}

$server = new SimpleStreamServer(new HttpServer(new AppRequestHandler, "http", "localhost"));
$server->listen();
