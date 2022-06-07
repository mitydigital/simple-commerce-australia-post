<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Console\Commands;

use Statamic\Console\Commands\GeneratorCommand as StatamicGeneratorCommand;

class GeneratorCommand extends StatamicGeneratorCommand
{
    /**
     * Taken from Simple Commerce
     *
     * We need to do this ourselves so it uses the
     * Simple Commerce Australia Post stub path.
     *
     * @return string
     */
    protected function getStub($stub = null): string
    {
        $stub = $stub ?? $this->stub;

        return __DIR__.'/stubs/'.$stub;
    }
}
