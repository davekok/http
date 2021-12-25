<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\Activity;
use davekok\kernel\Actionable;
use davekok\kernel\Readable;
use davekok\kernel\Writable;
use davekok\kernel\Cryptoble;
use davekok\kernel\Url;

class ActiveHttpActionable implements Actionable
{
    public function __construct(
        private readonly HttpFactory $httpFactory,
        private readonly Actionable $actionable,
        private readonly HttpUrl $url,
    ) {
        $this->actionable instanceof Readable ?: throw new InvalidArgumentException("Expected an readable actionable.");
        $this->actionable instanceof Writable ?: throw new InvalidArgumentException("Expected an writable actionable.");
        if ($this->actionable instanceof Cryptoble) {
            // $this->actionable->enableCrypto(true, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);
        }
    }

    public function actionable(): Actionable
    {
        return $this->actionable;
    }

    public function activity(): Activity
    {
        return $this->actionable->activity();
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function read(callback $handler): void
    {
        $this->actionable->read(
            $this->httpFactory->createReader($this->actionable instanceof Cryptoble ? $this->actionable->isCryptoEnabled() : false),
            $handler
        );
    }

    public function write(HttpMessage $message): void
    {
        $this->actionable->write($this->httpFactory->createWriter($message));
    }
}
