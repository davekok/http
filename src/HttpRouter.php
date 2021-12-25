<?php

declare(strict_types=1);

namespace davekok\http;

use Throwable;

class HttpRouter
{
    public function __construct(
        private readonly HttpMounts $mounts,
        private readonly ActiveHttpActionable $actionable,
    ) {
        $this->actionable->read($this->handleHttp(...));
        $this->actionable->activity()->loop();
    }

    private function handleHttp(HttpMessage|null $message): void
    {
        try {
            if ($message instanceof HttpRequest) {
                $this->actionable->write($this->mounts->findMount($message->url->path)->handleHttpRequest($message));
            } else {
                $this->actionable->close();
            }
        } catch (HttpNotFoundException $throwable) {
            $this->actionable->write(new HttpResponse(status: HttpStatus::NOT_FOUND));
        } catch (Throwable $throwable) {
            $this->actionable->write(new HttpResponse(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body:   <<<TEXT
                    Internal server error
                    ---------------------
                    {$throwable->getMessage()}
                    TEXT
            ));
        }
    }
}
