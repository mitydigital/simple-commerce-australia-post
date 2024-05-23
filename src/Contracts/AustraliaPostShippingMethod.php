<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Contracts;

interface AustraliaPostShippingMethod
{
    public function domesticServiceCode(): string|false;

    public function internationalServiceCode(): string|false;
}
