<?php

namespace Tabula17\Satelles\Utilis\Config;

use Tabula17\Satelles\Utilis\Collection\BaseParamsCollection;
use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

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
            get {
                return array_map(static fn($value, $key) => str_contains($value, ":") ? ucfirst($value) : "$key: $value", $this->headers, array_keys($this->headers));
            }
        }
    protected(set) bool $requiresAuth = false;
    protected(set) bool $pathParams = false;
    protected(set) BaseParamsCollection $params
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
}