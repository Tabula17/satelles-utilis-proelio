<?php

namespace Tabula17\Satelles\Utilis\Config;

use JsonSerializable;
use Tabula17\Satelles\Utilis\Interface\EnumMethodsInterface;

enum HttpMethodEnum implements JsonSerializable
{
    case GET;
    case POST;
    case PUT;
    case DELETE;
    case PATCH;
    case HEAD;
    case OPTIONS;
    case TRACE;
    case CONNECT;

    public function value(): string
    {
        return $this->name;
    }

    public static function fromString(string $method): ?self
    {
        return match (strtoupper($method)) {
            'GET' => self::GET,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'DELETE' => self::DELETE,
            'PATCH' => self::PATCH,
            'HEAD' => self::HEAD,
            'OPTIONS' => self::OPTIONS,
            'TRACE' => self::TRACE,
            'CONNECT' => self::CONNECT,
            default => null,
        };
    }

    public function jsonSerialize(): mixed
    {
        return $this->value();
    }
}
