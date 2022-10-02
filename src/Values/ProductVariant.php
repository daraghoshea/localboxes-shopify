<?php

namespace Localboxes\Source\Shopify\Values;

use Spatie\DataTransferObject\DataTransferObject;

class ProductVariant extends DataTransferObject
{
    public int $id;
    public string $title;
    public string $price;
    public ?string $compare_at_price;
    public ?int $position;
    public ?string $option1;
    public ?string $option2;
    public ?string $option3;
    public ?string $inventory_policy;
    public ?int $inventory_quantity;
    public ?int $old_inventory_quantity;

    public function inventoryKnown() :bool
    {
        return ! is_null($this->inventory_quantity);
    }

    public function inStock() :bool
    {
        return ! is_null($this->inventory_quantity) && $this->inventory_quantity > 0;
    }

    public function outOfStock() :bool
    {
        return ! is_null($this->inventory_quantity) && $this->inventory_quantity === 0;
    }

    public function price():int
    {
        return (int) (((float) $this->price) * 100);
    }

    public function compareAtPrice():int
    {
        return (int) (((float) $this->compare_at_price ?? 0) * 100);
    }

    public function isReduced(): bool
    {
        return $this->compareAtPrice() > $this->price();
    }

    public function priceReduction(): int
    {
        return $this->isReduced() ? $this->compareAtPrice() - $this->price() : 0;
    }

    public function priceDiscount(): float
    {
        return ($this->priceReduction() / $this->compareAtPrice()) * 100;
    }
}
