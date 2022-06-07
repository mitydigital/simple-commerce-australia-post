<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Console\Commands;

use MityDigital\SimpleCommerceAustraliaPost\Services\AustraliaPostPostageAssessmentCalculator;

class DomesticParcelServicesCommand extends BaseServiceCommand
{
    protected $signature = 'scap:domestic-parcel-services {toPostcode : The destination postcode}';

    protected $description = 'List the Australia Post Domestic Parcel Services';

    protected function callService(AustraliaPostPostageAssessmentCalculator $service)
    {
        $toPostcode = $this->argument('toPostcode');

        return $service->listDomesticParcelServices($toPostcode);
    }
}
