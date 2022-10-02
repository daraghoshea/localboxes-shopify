<?php

namespace Localboxes\Source\Shopify\Values;

use Illuminate\Support\Collection;
use JessArcher\CastableDataTransferObject\CastableDataTransferObject;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class ProductData extends CastableDataTransferObject
{
    public bool $ignoreMissing = true;

    public string $url;
    public int $id;
    public string $title;
    public string $handle;
    public ?string $vendor;
    public ?string $body_html;
    public ?ProductImage $image;

    /** @var ProductVariant[] */
    #[CastWith(ArrayCaster::class, itemType: ProductVariant::class)]
    public ?array $variants;

    /** @var ProductOption[] */
    #[CastWith(ArrayCaster::class, itemType: ProductOption::class)]
    public ?array $options;

    /** @var ProductImage[] */
    #[CastWith(ArrayCaster::class, itemType: ProductImage::class)]
    public ?array $images;

    public static function fromShopifyJson(string $json) : static
    {
        $data = json_decode($json, true);

        return new static($data['product']);
    }

    public function prices() :Collection
    {
        return collect($this->variants)->map(function(ProductVariant $variant) {
            return $variant->price();
        });
    }

    public function price(): int
    {
        return $this->prices()->min();
    }

    public function image() : ?ProductImage
    {
        if($this->image) {
            return $this->image;
        }

        if(!empty($this->images)) {
            return $this->images[0];
        }

        return null;
    }

    public function compareAtPrice(): int
    {
        $variant = collect($this->variants)->first(fn($variant) => $variant->isReduced());

        return $variant ? $variant->compareAtPrice() : $this->price();
    }

    public function priceFormatted(): string
    {
        return number_format($this->price()/100, 2);
    }

    public function isReduced():bool
    {
        return collect($this->variants)->reduce(function($isReduced, ProductVariant $variant) {
            return $isReduced || $variant->isReduced();
        }, false);
    }

    public function priceReduction():int
    {
        $variant = collect($this->variants)->first(fn($variant) => $variant->isReduced());

        return $variant ? $variant->priceReduction() : 0;
    }

    public function priceDiscount() :int
    {
        $variant = collect($this->variants)->first(fn($variant) => $variant->isReduced());

        return $variant ? intval($variant->priceDiscount()) : 0;
    }

    public function multiplePrices() :bool
    {
        return $this->prices()->unique()->count() > 1;
    }

    // TODO rename to multipleVariants
    public function multipleOptions() :bool
    {
        return count($this->variants) > 1;
    }

    public function outOfStock() :bool
    {
        $stock = $this->stock();

        return $stock->known && $stock->quantity === 0;
    }

    public function stock(): \stdClass
    {
        return collect($this->variants)->reduce(function($current, $variant) {
            $current->known = ($current->known || !is_null($variant->inventory_quantity));
            $current->quantity += (int) $variant->inventory_quantity ?? 0;
            return $current;
        }, (object) ['known' => false, 'quantity' => 0]);
    }
}
