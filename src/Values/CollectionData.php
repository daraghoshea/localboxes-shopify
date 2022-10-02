<?php

namespace Localboxes\Source\Shopify\Values;

use Carbon\Carbon;
use JessArcher\CastableDataTransferObject\CastableDataTransferObject;
use Localboxes\Source\Shopify\Shared\DateCaster;
use Spatie\DataTransferObject\Attributes\CastWith;

class CollectionData extends CastableDataTransferObject
{
    public bool $ignoreMissing = true;

    public int $id;
    public string $title;
    public string $handle;
    public ?string $description;
    public int $products_count;

    #[CastWith(DateCaster::class)]
    public Carbon $updated_at;
}