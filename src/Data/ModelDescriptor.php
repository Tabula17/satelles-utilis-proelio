<?php

namespace Tabula17\Satelles\Utilis\Data;

use Closure;
use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

/**
 * Represents a model descriptor defining metadata about a data model, including its type, primitive type,
 * and optional hydration logic.
 *
 * This class extends AbstractDescriptor and provides mechanisms for handling data types and custom hydrate
 * closures. It also manages metadata such as title, description, and format.
 *
 * @property-read string $type Retrieves the data type value from the associated DataTypes instance.
 * @property-read string $primitiveType Retrieves the primitive type from the associated DataTypes instance.
 * @property-read ?Closure $hydrate Optional closure responsible for hydration, either provided or derived from the DataTypes instance.
 */
class ModelDescriptor extends AbstractDescriptor
{
    public string $type
        {
            get => $this->dataTypes->value;
        }
    public string $primitiveType
        {
            get => $this->dataTypes->primitiveType();
        }
    public readonly ?Closure $hydrate;

    /**
     * @param string $title
     * @param string|null $description
     * @param DataTypes $dataTypes
     * @param string|null $format
     * @param Closure|null $hydrate
     */
    public function __construct(
        public readonly string     $title,
        public readonly ?string    $description = null,
        private readonly DataTypes $dataTypes = DataTypes::STR,
        public readonly ?string    $format = null,
        ?Closure                   $hydrate = null
    )
    {
        //$this->primitiveType = $dataTypes->primitiveType();
        $this->hydrate = $hydrate ?? $dataTypes->hydrate(...);
        parent::__construct();
    }
    public function withHydrate(Closure $hydrate): static
    {
        return new static(
            title: $this->title,
            description: $this->description,
            dataTypes: $this->dataTypes,
            format: $this->format,
            hydrate: $hydrate
        );
    }
}