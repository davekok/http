<?php

declare(strict_types=1);

namespace davekok\http;

enum HttpStatus: int
{
    case CONTINUE                             = 100;
    case SWITCHING_PROTOCOLS                  = 101;
    case PROCESSING                           = 102; // RFC2518
    case EARLY_HINTS                          = 103; // RFC8297
    case OK                                   = 200;
    case CREATED                              = 201;
    case ACCEPTED                             = 202;
    case NON_AUTHORITATIVE_INFORMATION        = 203;
    case NO_CONTENT                           = 204;
    case RESET_CONTENT                        = 205;
    case PARTIAL_CONTENT                      = 206;
    case MULTI_STATUS                         = 207; // RFC4918
    case ALREADY_REPORTED                     = 208; // RFC5842
    case IM_USED                              = 226; // RFC3229
    case MULTIPLE_CHOICES                     = 300;
    case MOVED_PERMANENTLY                    = 301;
    case FOUND                                = 302;
    case SEE_OTHER                            = 303;
    case NOT_MODIFIED                         = 304;
    case USE_PROXY                            = 305;
    case TEMPORARY_REDIRECT                   = 307;
    case PERMANENTLY_REDIRECT                 = 308; // RFC7238
    case BAD_REQUEST                          = 400;
    case UNAUTHORIZED                         = 401;
    case PAYMENT_REQUIRED                     = 402;
    case FORBIDDEN                            = 403;
    case NOT_FOUND                            = 404;
    case METHOD_NOT_ALLOWED                   = 405;
    case NOT_ACCEPTABLE                       = 406;
    case PROXY_AUTHENTICATION_REQUIRED        = 407;
    case REQUEST_TIMEOUT                      = 408;
    case CONFLICT                             = 409;
    case GONE                                 = 410;
    case LENGTH_REQUIRED                      = 411;
    case PRECONDITION_FAILED                  = 412;
    case REQUEST_ENTITY_TOO_LARGE             = 413;
    case REQUEST_URI_TOO_LONG                 = 414;
    case UNSUPPORTED_MEDIA_TYPE               = 415;
    case REQUESTED_RANGE_NOT_SATISFIABLE      = 416;
    case EXPECTATION_FAILED                   = 417;
    case I_AM_A_TEAPOT                        = 418; // RFC2324
    case MISDIRECTED_REQUEST                  = 421; // RFC7540
    case UNPROCESSABLE_ENTITY                 = 422; // RFC4918
    case LOCKED                               = 423; // RFC4918
    case FAILED_DEPENDENCY                    = 424; // RFC4918
    case TOO_EARLY                            = 425; // RFC-ietf-httpbis-replay-04
    case UPGRADE_REQUIRED                     = 426; // RFC2817
    case PRECONDITION_REQUIRED                = 428; // RFC6585
    case TOO_MANY_REQUESTS                    = 429; // RFC6585
    case REQUEST_HEADER_FIELDS_TOO_LARGE      = 431; // RFC6585
    case UNAVAILABLE_FOR_LEGAL_REASONS        = 451;
    case INTERNAL_SERVER_ERROR                = 500;
    case NOT_IMPLEMENTED                      = 501;
    case BAD_GATEWAY                          = 502;
    case SERVICE_UNAVAILABLE                  = 503;
    case GATEWAY_TIMEOUT                      = 504;
    case VERSION_NOT_SUPPORTED                = 505;
    case VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506; // RFC2295
    case INSUFFICIENT_STORAGE                 = 507; // RFC4918
    case LOOP_DETECTED                        = 508; // RFC5842
    case NOT_EXTENDED                         = 510; // RFC2774
    case NETWORK_AUTHENTICATION_REQUIRED      = 511; // RFC6585

    public function code(): int
    {
        return $this->value;
    }

    public function text(): string
    {
        return match($this) {
            static::CONTINUE                             => 'Continue',
            static::SWITCHING_PROTOCOLS                  => 'Switching Protocols',
            static::PROCESSING                           => 'Processing',
            static::EARLY_HINTS                          => 'Early Hints',
            static::OK                                   => 'OK',
            static::CREATED                              => 'Created',
            static::ACCEPTED                             => 'Accepted',
            static::NON_AUTHORITATIVE_INFORMATION        => 'Non-Authoritative Information',
            static::NO_CONTENT                           => 'No Content',
            static::RESET_CONTENT                        => 'Reset Content',
            static::PARTIAL_CONTENT                      => 'Partial Content',
            static::MULTI_STATUS                         => 'Multi-Status',
            static::ALREADY_REPORTED                     => 'Already Reported',
            static::IM_USED                              => 'IM Used',
            static::MULTIPLE_CHOICES                     => 'Multiple Choices',
            static::MOVED_PERMANENTLY                    => 'Moved Permanently',
            static::FOUND                                => 'Found',
            static::SEE_OTHER                            => 'See Other',
            static::NOT_MODIFIED                         => 'Not Modified',
            static::USE_PROXY                            => 'Use Proxy',
            static::TEMPORARY_REDIRECT                   => 'Temporary Redirect',
            static::PERMANENTLY_REDIRECT                 => 'Permanent Redirect',
            static::BAD_REQUEST                          => 'Bad Request',
            static::UNAUTHORIZED                         => 'Unauthorized',
            static::PAYMENT_REQUIRED                     => 'Payment Required',
            static::FORBIDDEN                            => 'Forbidden',
            static::NOT_FOUND                            => 'Not Found',
            static::METHOD_NOT_ALLOWED                   => 'Method Not Allowed',
            static::NOT_ACCEPTABLE                       => 'Not Acceptable',
            static::PROXY_AUTHENTICATION_REQUIRED        => 'Proxy Authentication Required',
            static::REQUEST_TIMEOUT                      => 'Request Timeout',
            static::CONFLICT                             => 'Conflict',
            static::GONE                                 => 'Gone',
            static::LENGTH_REQUIRED                      => 'Length Required',
            static::PRECONDITION_FAILED                  => 'Precondition Failed',
            static::REQUEST_ENTITY_TOO_LARGE             => 'Payload Too Large',
            static::REQUEST_URI_TOO_LONG                 => 'URI Too Long',
            static::UNSUPPORTED_MEDIA_TYPE               => 'Unsupported Media Type',
            static::REQUESTED_RANGE_NOT_SATISFIABLE      => 'Range Not Satisfiable',
            static::EXPECTATION_FAILED                   => 'Expectation Failed',
            static::I_AM_A_TEAPOT                        => 'I\'m a teapot',
            static::MISDIRECTED_REQUEST                  => 'Misdirected Request',
            static::UNPROCESSABLE_ENTITY                 => 'Unprocessable Entity',
            static::LOCKED                               => 'Locked',
            static::FAILED_DEPENDENCY                    => 'Failed Dependency',
            static::TOO_EARLY                            => 'Too Early',
            static::UPGRADE_REQUIRED                     => 'Upgrade Required',
            static::PRECONDITION_REQUIRED                => 'Precondition Required',
            static::TOO_MANY_REQUESTS                    => 'Too Many Requests',
            static::REQUEST_HEADER_FIELDS_TOO_LARGE      => 'Request Header Fields Too Large',
            static::UNAVAILABLE_FOR_LEGAL_REASONS        => 'Unavailable For Legal Reasons',
            static::INTERNAL_SERVER_ERROR                => 'Internal Server Error',
            static::NOT_IMPLEMENTED                      => 'Not Implemented',
            static::BAD_GATEWAY                          => 'Bad Gateway',
            static::SERVICE_UNAVAILABLE                  => 'Service Unavailable',
            static::GATEWAY_TIMEOUT                      => 'Gateway Timeout',
            static::VERSION_NOT_SUPPORTED                => 'HTTP Version Not Supported',
            static::VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL => 'Variant Also Negotiates',
            static::INSUFFICIENT_STORAGE                 => 'Insufficient Storage',
            static::LOOP_DETECTED                        => 'Loop Detected',
            static::NOT_EXTENDED                         => 'Not Extended',
            static::NETWORK_AUTHENTICATION_REQUIRED      => 'Network Authentication Required',
        };
    }
}
