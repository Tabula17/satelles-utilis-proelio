<?php

namespace Tabula17\Satelles\Utilis\Config;

use InvalidArgumentException;
use Tabula17\Satelles\Utilis\Collection\BaseParamsCollection;

class ApiPathConfig extends AbstractDescriptor
{
    protected(set) string $name;
    protected(set) string $path {
        set {
            $this->path = '/' . ltrim($value, '/');
        }
    }
    protected(set) ?HttpMethodEnum $method = null
        {
            set(HttpMethodEnum|string|null $value) {
                if (is_string($value)) {
                    $value = HttpMethodEnum::fromString($value);
                }
                $this->method = $value;
            }
        }
    protected(set) array $headers = []
        {
            set {
                $value = array_map(
                    static function ($value) {
                        if (!is_string(is_callable($value) ? $value() : $value)) {
                            throw new \InvalidArgumentException('Los valores de los headers deben ser cadenas');
                        }
                        return $value;
                    }, $value
                );
                $this->headers = $value;
            }
            get {
                $normalizer = static function ($value) {
                    return is_callable($value) ? $value() : $value;
                };

                return array_map($normalizer, $this->headers);
            }
        }
    public array $headersAsStrings {
        get {
            $normalizer = static function ($value, $key) {
                return is_numeric($key) && str_contains($value, ":") ? ucfirst($value) : "$key: $value";
            };

            return array_map($normalizer, $this->headers, array_keys($this->headers));
        }

    }
    protected(set) bool $requiresAuth = false;
    protected(set) bool $pathParams = false;

    protected(set) string $placeholder = ':%%name%%'; //{%%name%%} // etc ;
    protected(set) BaseParamsCollection $params
        {
            set (BaseParamsCollection|array $value) {
                if (is_array($value)) {
                    $value = BaseParamsCollection::fromArray($value);
                }
                $value->setPlaceholderMask($this->placeholder);
                $this->params = $value;
            }
            get {
                if (!isset($this->params)) {
                    $this->params = new BaseParamsCollection();
                    $this->params->setPlaceholderMask($this->placeholder);
                }
                return $this->params;
            }
        }
    protected(set) BaseParamsCollection $options
        {
            set (BaseParamsCollection|array $value) {
                if (is_array($value)) {
                    $value = BaseParamsCollection::fromArray($value);
                }
                $this->params = $value;
            }
            get {
                if (!isset($this->params)) {
                    $this->params = new BaseParamsCollection();
                }
                return $this->params;
            }
        }

    public function getQueryString(bool $onlyValid = true): string
    {
        return http_build_query($this->params->getValues($onlyValid));
    }

    public function getQueryStringWithPlaceholder(bool $onlyValid = true): string
    {
        return http_build_query($this->params->getPlaceholders($onlyValid));
    }

    public function getPathParamsString(bool $onlyValid = true): string
    {
        $placeholders = $this->params->getPlaceholders($onlyValid);
        return implode('/', array_merge(...array_map(fn($k, $v) => [$k, $v], array_keys($placeholders), $placeholders)));
    }

    public function getEndpoint(string $baseUrl = '', bool $withParamPath = false, bool $onlyValid = true): string
    {
        return rtrim($baseUrl, '/') . $this->path . ($withParamPath && $this->pathParams ? $this->getPathParamsString($onlyValid) : '');
    }
}