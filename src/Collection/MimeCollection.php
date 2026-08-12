<?php

namespace Tabula17\Satelles\Utilis\Collection;


use Tabula17\Satelles\Utilis\File\MimeTypes;
use Throwable;

class MimeCollection extends TypedEnumCollection
{
    protected static function getEnumType(): string
    {
        return MimeTypes::class;
    }

    public function getMimeTypes(): array
    {
        return $this->map(fn(MimeTypes $mime) => $mime->mime());
    }

    public function getExtensions(): array
    {
        return $this->map(fn(MimeTypes $mime) => $mime->extension());
    }

    public function getMimeTypesAndExtensions(): array
    {
        return array_combine($this->getMimeTypes(), $this->getExtensions());
    }

    public static function fromArray(array $config): static
    {
        $values = [];

        foreach ($config as $key => $item) {
            try {
                $values[$key] = static::cast($item);
            } catch (Throwable $e) {
                continue;
            }
        }

        return new static(...$values);
    }
}