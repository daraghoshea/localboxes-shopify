<?php

namespace Localboxes\Source\Shopify\Shared;

use Carbon\Carbon;
use Spatie\DataTransferObject\Caster;

class DateCaster implements Caster
{

    /**
     * @param  mixed  $value
     * @return Carbon|mixed Carbon instance or original value
     */
    public function cast(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return new Carbon($value);
    }
}