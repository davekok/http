<?php

declare(strict_types=1);

namespace davekok\http;

use davekok\kernel\SocketUrl;

class HttpUrl extends Url
{
    public function __construct(
        public readonly HttpFactory $httpFactory,
        public readonly SocketUrl   $socketUrl,

        string|null     $scheme   = null,
        string|null     $username = null,
        string|null     $password = null,
        string|null     $host     = null,
        int|null        $port     = null,
        string|null     $path     = null,
        string|null     $query    = null,
        string|int|null $fragment = null,
    ) {
        parent::__construct(
            scheme  : $scheme  ,
            username: $username,
            password: $password,
            host    : $host    ,
            port    : $port    ,
            path    : $path    ,
            query   : $query   ,
            fragment: $fragment,
        );
    }

    public function connect(Activity $activity, float|null $timeout, Options|array|null $options = null): ActiveHttpActionable
    {
        return new ActiveHttpActionable(
            httpFactory: $this->httpFactory,
            actionable:  $this->socketUrl->connect(activity: $activity, timeout: $timeout, options: $options),
            url:         $this,
        );
    }

    public function bind(Activity $activity, Options|array|null $options = null): PassiveHttpActionable
    {
        return new PassiveHttpActionable(
            httpFactory: $this->httpFactory,
            actionable:  $this->socketUrl->bind(activity: $activity, options: $options),
            url:         $this,
        );
    }
}
