<?php

namespace Localboxes\Source\Shopify;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\Factory as Client;
use Localboxes\Source\Shopify\Exceptions\InvalidProductData;
use Localboxes\Source\Shopify\Values\ProductData;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class FetchProducts
{
    private Client $client;

    public function __construct(
        private Uri $domain,
        private int $page = 1,
        private int $secondsBetweenPageFetches = 1,
    )
    {
        $this->client = new Client;
    }

    /**
     * @throws InvalidProductData
     */
    public function fetch(callable $callback) : \Iterator
    {
        $hasProducts = true;

        while($hasProducts) {
            $products = $this->fetchPage();

            if(empty($products['products'])) {
                $hasProducts = false;
            }

            yield $callback($products, $this->domain, $this->page);

            if ($this->secondsBetweenPageFetches) {
                sleep($this->secondsBetweenPageFetches);
            }

            $this->page++;
        }
    }

    /** @throws InvalidProductData */
    public function fetchPage() : array
    {
        $jsonUri = $this->domain->withPath('products.json')->withQuery("page={$this->page}");

        $response = $this->client->get($jsonUri);

        $products = json_decode($response->body(), true);

        if(json_last_error() != JSON_ERROR_NONE) {
            throw InvalidProductData::invalidProductJson($jsonUri);
        }

        if(!isset($products['products']) || !is_array($products['products'])) {
            throw InvalidProductData::invalidData($products);
        }

        return array_map([$this,'arrayToValueObject'], $products['products']);
    }

    /** @throws InvalidProductData */
    private function arrayToValueObject(array $product) : ProductData
    {
        try {
            return new ProductData($product);
        } catch (UnknownProperties $e) {
            throw InvalidProductData::invalidValueObject($product);
        } catch (\Throwable $e) {
            throw new InvalidProductData($e->getMessage(),null, $e);
        }
    }
}