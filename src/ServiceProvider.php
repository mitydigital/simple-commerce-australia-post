<?php

namespace MityDigital\SimpleCommerceAustraliaPost;

use MityDigital\SimpleCommerceAustraliaPost\Console\Commands\DomesticParcelServicesCommand;
use MityDigital\SimpleCommerceAustraliaPost\Console\Commands\InternationalParcelServicesCommand;
use MityDigital\SimpleCommerceAustraliaPost\Console\Commands\MakeAustraliaPostShippingMethod;
use MityDigital\SimpleCommerceAustraliaPost\Fieldtypes\AustraliaPostShippingMethods;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        DomesticParcelServicesCommand::class,
        InternationalParcelServicesCommand::class,
        MakeAustraliaPostShippingMethod::class
    ];

    protected $fieldtypes = [
        AustraliaPostShippingMethods::class
    ];

    protected $scripts = [
        __DIR__.'/../dist/js/australia-post-shipping-methods.js'
    ];

    public function bootAddon()
    {
    }
}
