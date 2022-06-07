<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Contracts;

use MityDigital\SimpleCommerceAustraliaPost\Enums\AustraliaPostLocation;

interface AustraliaPostShippingMethod
{
    public function domesticServiceCode(): string|false;

    public function internationalServiceCode(): string|false;
}
