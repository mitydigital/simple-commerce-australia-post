<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Console\Commands;

use MityDigital\SimpleCommerceAustraliaPost\Services\AustraliaPostPostageAssessmentCalculator;

class InternationalParcelServicesCommand extends BaseServiceCommand
{
    protected $signature = 'scap:international-parcel-services {countryCode : The destination country code}';

    protected $description = 'List the Australia Post International Parcel Services';

    protected function callService(AustraliaPostPostageAssessmentCalculator $service)
    {
        $countryCode = $this->argument('countryCode');

        return $service->listInternationalParcelServices($countryCode);
    }
}
