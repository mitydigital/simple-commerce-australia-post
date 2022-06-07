<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Console\Commands;

use Statamic\Console\RunsInPlease;

class MakeAustraliaPostShippingMethod extends GeneratorCommand
{
    use RunsInPlease;

    protected $name = 'statamic:make:australia-post-shipping-method';
    protected $description = 'Create a new Australia Post shipping method';
    protected $type = 'ShippingMethod';
    protected $stub = 'australia-post-shipping.php.stub';

    public function handle()
    {
        if (parent::handle() === false) {
            return false;
        }
    }
}
