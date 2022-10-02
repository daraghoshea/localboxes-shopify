<?php

namespace Localboxes\Source\Shopify\Values;

use Spatie\DataTransferObject\DataTransferObject;

class ProductOption extends DataTransferObject
{
    public ?int $id;
    public ?string $name;
    public ?array $values;
}
