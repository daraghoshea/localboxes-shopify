<?php

namespace Localboxes\Source\Shopify\Exceptions;

use Exception;

class InvalidProductData extends Exception
{
    public $data;

    public static function invalidProductJson($url)
    {
        return new static("Invalid product json returned from {$url}");
    }

    public static function invalidData($data, ?\Throwable $e = null)
    {
        return tap(new static("Unexpected product data provided", 0, $e), function ($e) use ($data) {
            $e->data = $data;
        });
    }

    public static function invalidValueObject($data, ?\Throwable $e = null)
    {
        return tap(new static("Shopify product data did not satisfy the value object", 0, $e), function ($e) use ($data) {
            $e->data = $data;
        });
    }

    public static function invalidCollection($data, ?\Throwable $e = null)
    {
        return tap(new static("Unexpected collection data provided", 0, $e), function ($e) use ($data) {
            $e->data = $data;
        });
    }
}
