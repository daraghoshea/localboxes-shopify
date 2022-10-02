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
        Client $client = null,
    )
    {
        $this->client = $client ?? new Client;
    }

    /**
     * @throws InvalidProductData
     */
    public function fetch() : \Iterator
    {
        $hasProducts = true;

        while($hasProducts) {
            $products = $this->fetchPage();

            if(empty($products)) {
                $hasProducts = false;
                continue;
            }

            yield $products;

            if ($this->secondsBetweenPageFetches) {
                sleep($this->secondsBetweenPageFetches);
            }

            $this->page++;
        }
    }

    /** @throws InvalidProductData */
    private function fetchPage() : array
    {
        $jsonUri = $this->domain->withPath('/products.json')->withQuery("page={$this->page}");

        $response = $this->client->get($jsonUri);

        $data = json_decode($response->body(), true);

        if(json_last_error() != JSON_ERROR_NONE) {
            throw InvalidProductData::invalidProductJson($jsonUri);
        }

        if(!isset($data['products']) || !is_array($data['products'])) {
            throw InvalidProductData::invalidData($data);
        }

        return array_map([$this,'arrayToValueObject'], $data['products']);
    }

    /** @throws InvalidProductData */
    private function arrayToValueObject(array $product) : ProductData
    {
        $data = array_merge(
            $product,
            [
                'url' => isset($product['handle'])
                    ? (string) $this->domain->withPath('/products/' . $product['handle'])
                    : ''
            ]
        );

        try {
            return new ProductData($data);
        } catch (UnknownProperties|\TypeError $e) {
            throw InvalidProductData::invalidValueObject($data, $e);
        } catch (\Throwable $e) {
            throw InvalidProductData::fromThrowable($e, $data);
        }
    }
}