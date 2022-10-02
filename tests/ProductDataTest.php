<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\Factory;
use Localboxes\Source\Shopify\FetchProducts;
use Localboxes\Source\Shopify\Values\ProductData;
use function PHPUnit\Framework\assertTrue;

it('creates a value object with data', function () {
    $content = json_decode(file_get_contents(__DIR__ . '/fixtures/product.json'), true);

    $data = $content['product'];
    $data['url'] = '';

    $product = new ProductData($data);

    expect($product->title)->toEqual($data['title']);
});

it('adds the url when converting array to object value in fetcher', function () {
    $fakeClient = new Factory;

    $fakeClient->fake([
        'test.com/products.json?page=1' => Factory::response(file_get_contents(__DIR__ . '/fixtures/products.json'), 200),
        'test.com/products.json?page=2' => Factory::response(json_encode(['products' => []]), 200),
    ]);

    $fetcher = new FetchProducts(new Uri('http://test.com'), client: $fakeClient);

    $count = 0;
    $productPages = $fetcher->fetch();

    foreach($productPages as $products) {
        $count++;
        expect($products)->toHaveCount(17)
            ->each->toBeInstanceOf(ProductData::class);
    }

    expect($count)->toEqual(1);
});
