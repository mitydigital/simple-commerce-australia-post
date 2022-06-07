<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Console\Commands;

use Illuminate\Console\Command;
use MityDigital\SimpleCommerceAustraliaPost\Services\AustraliaPostPostageAssessmentCalculator;

abstract class BaseServiceCommand extends Command
{
    abstract protected function callService(AustraliaPostPostageAssessmentCalculator $service);

    public function handle(AustraliaPostPostageAssessmentCalculator $service)
    {
        $response = $this->callService($service);

        if (!$response->ok()) {
            $this->error('The Australia Post PAC returned an invalid response.');
            $this->error($response->body());
            return;
        }

        // get the body
        $body = $response->json();

        if (!isset($body['services']) || !isset($body['services']['service'])) {
            $this->error('Unexpected response from the Australia Post PAC: a list of services was not returned.');
            return;
        }

        $services = [];
        foreach ($body['services']['service'] as $service) {
            $services[] = [
                'Code' => $service['code'],
                'Name' => $service['name']
            ];
        }

        $this->table(['Code', 'Name'], $services);
    }
}