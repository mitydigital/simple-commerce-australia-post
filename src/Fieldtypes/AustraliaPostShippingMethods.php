<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Fieldtypes;

use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes\HasSelectOptions;

class AustraliaPostShippingMethods extends Fieldtype
{
    use HasSelectOptions;

    protected static $title = 'AP Shipping Methods';

    protected $categories = ['special'];

    public function preload(): array
    {
        return [
            'options' => SimpleCommerce::shippingMethods(Site::current()->handle())
                ->mapWithKeys(fn($shippingMethod) => [
                    $shippingMethod['class'] => $shippingMethod['name']
                ])
        ];
    }

    protected function multiple()
    {
        return true;
    }
}
