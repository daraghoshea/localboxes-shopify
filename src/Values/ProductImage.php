<?php

namespace Localboxes\Source\Shopify\Values;

use Spatie\DataTransferObject\DataTransferObject;

class ProductImage extends DataTransferObject
{
    const ALLOWED_SIZES = [
        'small' => 100,
        'medium' => 240,
        'large' => 480
    ];

    public int $id;
    public ?int $position;
    public ?string $alt;
    public ?int $width;
    public ?int $height;
    public string $src;

    public function size($width, $height = null)
    {
        if(is_numeric($width)) {
            return $this->sizeNumeric($width, $height);
        }

        return $this->sizeStandard($width);
    }

    private function sizeNumeric($newWidth, $newHeight)
    {
        $width = $newWidth;
        $height = $this->round($width / $this->aspectRatio());

        // Shopify doesn't crop, so need to keep aspect ratio if height specified
        if($newHeight && $height > $newHeight) {
            $height = $newHeight;
            $width = $this->round($height * $this->aspectRatio());
        }

        return new static(array_merge($this->toArray(), [
            'width' => $width,
            'height' => $height,
            'src' => $this->url($newWidth, $newHeight)
        ]));
    }

    private function sizeStandard($size)
    {
        $size = strtolower($size);

        if(!array_key_exists($size, self::ALLOWED_SIZES)) {
            throw new \InvalidArgumentException("{size} is not a valid shopify image size.");
        }

        $sizeMax = self::ALLOWED_SIZES[$size];

        $width = $sizeMax;
        $height = $this->round($width / $this->aspectRatio());

        if($height > $sizeMax) {
            $height = $sizeMax;
            $width = $this->round($height * $this->aspectRatio());
        }

        return new static(array_merge($this->toArray(), [
            'width' => $width,
            'height' => $height,
            'src' => $this->url($size)
        ]));
    }

    private function aspectRatio()
    {
        return $this->width / $this->height;
    }

    private function round($number)
    {
        return intval($this->roundToNearest($number, 1));
    }

    private function roundUpToNext($n,$next=5) {
        return round(($n+$next/2)/$next)*$next;
    }

    private function roundToNearest($n, $nearest = 5)
    {
        return (round($n)%$nearest === 0) ? round($n) : round(($n+$nearest/2)/$nearest)*$nearest;
    }

    private function roundUpToNearest($n, $nearest=5)
    {
        return (ceil($n)%$nearest === 0) ? ceil($n) : round(($n+$nearest/2)/$nearest)*$nearest;
    }

    /**
     * Shopify Image Urls can be edited to generate the required size
     * Integers: 200x200, 400x, etc
     * Strings
     *
     * @param ?int|string $width
     * @param ?int|string $height
     * @return string
     */
    public function url($width = null, $height = null):string
    {
        if(!$width && !$height) {
            return $this->src;
        }

        ['filename' => $filename, 'extension' => $extension] = pathinfo(parse_url($this->src)['path']);

        $size = $this->sanitizeSize($width,$height);

        return str_replace("{$filename}.{$extension}", "{$filename}_{$size}.{$extension}", $this->src);
    }

    private function sanitizeSize($width,$height):string
    {
        if(is_numeric($width)) {
            return "{$width}x{$height}";
        }

        $width = strtolower($width);

        return array_key_exists($width, self::ALLOWED_SIZES) ? $width : '';
    }
}
