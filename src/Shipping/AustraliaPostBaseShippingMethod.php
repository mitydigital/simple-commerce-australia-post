<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Shipping;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Contracts\ShippingMethod;
use DoubleThreeDigital\SimpleCommerce\Orders\Address;
use DoubleThreeDigital\SimpleCommerce\Shipping\BaseShippingMethod;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use MityDigital\SimpleCommerceAustraliaPost\Contracts\AustraliaPostShippingMethod;
use MityDigital\SimpleCommerceAustraliaPost\Enums\AustraliaPostLocation;
use MityDigital\SimpleCommerceAustraliaPost\Services\AustraliaPostPostageAssessmentCalculator;
use Statamic\Facades\Blink;

abstract class AustraliaPostBaseShippingMethod extends BaseShippingMethod implements ShippingMethod, AustraliaPostShippingMethod
{
    // weight restrictions
    protected const DOMESTIC_MAX_WEIGHT = 22;
    protected const INTL_MAX_WEIGHT = 20;

    // dimension restrictions
    protected const MAX_DIMENSION = 105;
    protected const MAX_CUBIC_METRES = 0.25;
    protected const MAX_GIRTH = 140;

    // minimum girths
    protected const MIN_HEIGHT = 1;
    protected const MIN_LENGTH = 1;
    protected const MIN_WIDTH = 1;
    protected const MIN_WEIGHT = 0.01;

    // internal properties
    protected array|null $packages = null;
    protected float|null $cost = null;
    protected bool $freeShipping = false;
    protected AustraliaPostLocation|null $location = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;

        // update internal properties
        $this->blinkGet();
    }

    protected function blinkGet()
    {
        // get the properties from the blink store
        $properties = Blink::store($this->blinkStore())->get(get_class($this), [
            'packages' => $this->packages,
            'cost' => $this->cost,
            'freeShipping' => $this->freeShipping,
            'location' => $this->location
        ]);

        // update internal properties
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    protected function blinkStore()
    {
        return 'scap';
    }

    public function calculateCost(Order $order): int
    {
        // if there is no cost (it is null) run the availability check again
        // this usually happens when the shipping method is selected, and the user
        // progresses to the next stage of the checkout process.
        if ($this->cost === null) {
            $this->checkAvailability($order, Address::from('shipping', $order));
        }

        // convert to cents, and return
        return (int) ($this->cost * 100);
    }

    public function checkAvailability(Order $order, Address $address): bool
    {
        // is the shipping location allowed for the order's address?
        if (!$this->isLocationAllowedForAddress($address)) {
            return false;
        }

        if (!$this->packages) {
            $this->buildPackages($order);
        }

        // if we have a package, or the order is marked as free shipping
        if (count($this->packages) > 0 || $this->freeShipping) {
            // calculate the shipping cost
            $this->calculateAustraliaPostShippingCost($address);
        }

        // save everything we've done so far
        $this->blinkPut();

        if ($this->cost === null) {
            // there is no cost, but not 0
            return false;
        }

        return true;
    }

    protected function isLocationAllowedForAddress(Address $address): bool
    {
        $country = $address->country();
        if (!isset($country) || !isset($country['iso'])) {
            // there is no country
            return false;
        }

        if ($country['iso'] == 'AU') {
            // country is Australia - does this shipping method allow domestic?
            if (!$this->domesticServiceCode()) {
                return false;
            }

            // mark as domestic
            $this->location = AustraliaPostLocation::DOMESTIC;
        } else {
            // country is NOT Australia - does this shipping method allow international?
            if (!$this->internationalServiceCode()) {
                return false;
            }

            // mark as international
            $this->location = AustraliaPostLocation::INTERNATIONAL;
        }

        return true;
    }

    protected function buildPackages(Order $order)
    {
        // set packages to an empty array
        $this->packages = [];

        // we require a location
        if (!$this->location) {
            return;
        }

        // convert the line items to an array of items based on the quantities of each
        $items = [];

        // get the field used for free shipping
        $excludeFromShippingField = config('simple-commerce-australia-post.mappings.exclude_from_calculations',
            'exclude_from_calculations');
        $hasAtLeastOneExcludeFromShippingProduct = false;

        foreach ($order->lineItems as $lineItem) {

            // is this item marked as free shipping? if so, skip it
            $excludeFromShipping = $lineItem->product->get($excludeFromShippingField);
            if ($excludeFromShipping && is_array($excludeFromShipping) && in_array(get_class($this),
                    $excludeFromShipping)) {
                // mark as order having an excluded product
                // this is so we can still use the shipping method (as $0.00) even for free shipping products
                $hasAtLeastOneExcludeFromShippingProduct = true;

                // skip adding product to list
                continue;
            }

            for ($i = 0; $i < $lineItem->quantity; $i++) {
                $items[] = [
                    'width' => $lineItem->product->get('width'),
                    'height' => $lineItem->product->get('height'),
                    'length' => $lineItem->product->get('depth'),
                    'weight' => $lineItem->product->get('weight'),
                ];
            }
        }

        // if there are no items, let's get out (or mark as having free shipping if all are free)
        if (!count($items)) {
            // if we have no items, but we have at least one product excluded from shipping, then we have a $0.00 shipping for this order
            if ($hasAtLeastOneExcludeFromShippingProduct) {
                // mark as being valid
                $this->freeShipping = true;
            }

            // no need to do any more
            return;
        }

        // sort by height
        usort($items, function ($a, $b) {
            return $a['height'] - $b['height'];
        });

        // set the max weight for the shipping method
        $maxWeight = self::DOMESTIC_MAX_WEIGHT;
        if ($this->location === AustraliaPostLocation::INTERNATIONAL) {
            $maxWeight = self::INTL_MAX_WEIGHT;
        }

        foreach ($items as $item) {
            $height = $item['height'];
            $length = $item['length'];
            $width = $item['width'];

            $weight = $item['weight'];

            // calculate cubic weight, in kg
            // see https://auspost.com.au/business/shipping/check-sending-guidelines/size-weight-guidelines for formula
            $cubicMetres = $this->calculateCubicMetres($height, $length, $width);
            $cubicWeight = $cubicMetres * 250;

            // do checks based on max dimensions/weight
            if ($cubicWeight > $maxWeight) {
                // cannot exceed $maxWeight
                return false;
            }

            if (max($height, $length, $width) > self::MAX_DIMENSION) {
                // one dimension exceeds Australia Post's maximum
                return false;
            }

            switch ($this->location) {
                case AustraliaPostLocation::DOMESTIC:
                    if ($cubicMetres > self::MAX_CUBIC_METRES) {
                        // exceeds cubic metres
                        return false;
                    }
                    break;
                case AustraliaPostLocation::INTERNATIONAL:

                    // calculate girth
                    // see https://auspost.com.au/business/shipping/check-sending-guidelines/size-weight-guidelines
                    $dimensions = [$height, $length, $width];
                    sort($dimensions);

                    // length is longest
                    // two shortest used for girth
                    $girth = ($dimensions[0] + $dimensions[1]) * 2;

                    if ($girth > self::MAX_GIRTH) {
                        // exceeds girth
                        return false;
                    }
                    break;
            }

            for ($i = 0; $i < count($items); $i++) {
                // do we have a package already?
                if (!isset($this->packages[$i])) {
                    //
                    // no package yet, so create a package and add the item to it
                    //
                    $this->packages[$i] = [
                        'weight' => $weight,
                        'cubicWeight' => $cubicWeight,

                        'width' => $width,
                        'length' => $length,
                        'height' => $height,
                    ];
                    break;
                } else {
                    //
                    // package exists, so does this new item fit?
                    //
                    $package = &$this->packages[$i];

                    // get the minimum dimension
                    $minDimension = min($height, $length, $width);

                    $maxValue = $minDimension;
                    $newCubicMetres = $cubicMetres;

                    switch ($minDimension) {
                        case $height:
                            $newCubicMetres = $this->calculateCubicMetres(
                                $package['height'] + $height,
                                $package['length'],
                                $package['width']
                            );
                            $maxValue = max($package['height'] + $height, $package['length'], $package['width']);
                            break;
                        case $length:
                            $newCubicMetres = $this->calculateCubicMetres(
                                $package['height'],
                                $package['length'] + $length,
                                $package['width']
                            );
                            $maxValue = max($package['height'], $package['length'] + $length, $package['width']);
                            break;
                        case $width:
                            $newCubicMetres = $this->calculateCubicMetres(
                                $package['height'],
                                $package['length'],
                                $package['width'] + $width
                            );
                            $maxValue = max($package['height'], $package['length'], $package['width'] + $width);
                            break;
                    }

                    // can we add it to this package?
                    if (
                        $package['weight'] + $weight <= $maxWeight &&
                        $maxValue <= self::MAX_DIMENSION &&
                        $newCubicMetres <= self::MAX_CUBIC_METRES
                    ) {
                        // increase weights
                        $package['weight'] += $weight;
                        $package['cubicWeight'] += $cubicWeight;

                        switch ($minDimension) {
                            case $height:
                                $package['height'] += $height;
                                $package['length'] = max($package['length'], $length);
                                $package['width'] = max($package['width'], $width);
                                break;
                            case $length:
                                $package['height'] = max($package['height'], $height);
                                $package['length'] += $length;
                                $package['width'] = max($package['width'], $width);
                                break;
                            case $width:
                                $package['height'] = max($package['height'], $height);
                                $package['length'] = max($package['length'], $length);
                                $package['width'] += $width;
                                break;
                        }

                        break;
                    }
                }
            }
        }

        foreach ($this->packages as &$package) {
            // make sure we fit inside the minimum lengths
            if ($package['length'] < self::MIN_LENGTH) {
                $package['length'] = self::MIN_LENGTH;
            }
            if ($package['height'] < self::MIN_HEIGHT) {
                $package['height'] = self::MIN_HEIGHT;
            }
            if ($package['width'] < self::MIN_WIDTH) {
                $package['width'] = self::MIN_WIDTH;
            }
            if ($package['weight'] < self::MIN_WEIGHT) {
                $package['weight'] = self::MIN_WEIGHT;
            }
        }
    }

    protected function calculateCubicMetres($heightInCm, $lengthInCm, $widthInCm)
    {
        return ($widthInCm / 100) * ($lengthInCm / 100) * ($heightInCm / 100);
    }

    protected function calculateAustraliaPostShippingCost(Address $address): void
    {
        // set the default shipping cost
        $cost = 0;

        // if we have packages, ask Australia Post for a quote
        if (count($this->packages) > 0) {

            // make the service
            $service = App::make(AustraliaPostPostageAssessmentCalculator::class);

            switch ($this->location) {
                case AustraliaPostLocation::DOMESTIC:
                    // get the domestic cost
                    $responses = $service->batchCalculateDomesticParcelCost($this->domesticServiceCode(),
                        $address->zipCode(),
                        $this->packages);
                    break;
                case AustraliaPostLocation::INTERNATIONAL:
                    // get the international cost
                    $responses = $service->batchCalculateInternationalParcelCost($this->internationalServiceCode(),
                        $address->country()['iso'],
                        $this->packages);
                    break;
            }

            foreach ($responses as $response) {
                // if any one response fails, consider the request a total failure
                if (!$response->ok()) {
                    $error = $response->json();
                    if (isset($error['error']) && isset($error['error']['errorMessage'])) {
                        Log::error('PAC call failed: '.$error['error']['errorMessage'], $error);
                    } else {
                        Log::error('PAC call failed: missing \'errorMessage\' param.', $error);
                    }
                    return;
                }

                // get the body
                $body = $response->json();

                // look for "postage_result"
                if (!isset($body['postage_result']) || !isset($body['postage_result']['total_cost'])) {
                    Log::error('calculateAustraliaPostShippingCost could not find cost.', $response->json());
                    return;
                }

                // add the total cost
                $cost += $body['postage_result']['total_cost'];
            }
        }

        // if we make it this far, cost was successfully created
        $this->cost = $cost;
    }

    protected function blinkPut()
    {
        // save to the store
        Blink::store($this->blinkStore())->put(get_class($this), [
            'packages' => $this->packages,
            'cost' => $this->cost,
            'freeShipping' => $this->freeShipping,
            'location' => $this->location
        ]);
    }
}
