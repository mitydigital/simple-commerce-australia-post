<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class AustraliaPostPostageAssessmentCalculator
{

    protected null|string $apiKey = null;

    public function __construct()
    {
        $this->apiKey = config('simple-commerce-australia-post.api_key', null);
    }

    public function calculateDomesticParcelCost(string $serviceCode, string $toPostcode, array $package)
    {
        return Http::withHeaders([
            'auth-key' => $this->apiKey
        ])->get('https://digitalapi.auspost.com.au/postage/parcel/domestic/calculate', [
            'service_code' => $serviceCode,
            'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
            'to_postcode' => $toPostcode,
            'length' => $package['length'],
            'width' => $package['width'],
            'height' => $package['height'],
            'weight' => $package['weight']
        ]);
    }

    public function batchCalculateDomesticParcelCost(string $serviceCode, string $toPostcode, array $packages)
    {
        return Http::pool(function (Pool $pool) use ($serviceCode, $toPostcode, $packages) {
            foreach ($packages as $package) {
                $pool->withHeaders([
                    'auth-key' => $this->apiKey
                ])->get('https://digitalapi.auspost.com.au/postage/parcel/domestic/calculate', [
                    'service_code' => $serviceCode,
                    'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
                    'to_postcode' => $toPostcode,
                    'length' => $package['length'],
                    'width' => $package['width'],
                    'height' => $package['height'],
                    'weight' => $package['weight']
                ]);
            }
        });
    }

    public function calculateInternationalParcelCost(string $serviceCode, string $countryCode, array $package)
    {
        return Http::withHeaders([
            'auth-key' => $this->apiKey
        ])->get('https://digitalapi.auspost.com.au/postage/parcel/international/calculate', [
            'service_code' => $serviceCode,
            'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
            'country_code' => $countryCode,
            'weight' => $package['weight']
        ]);
    }

    public function batchCalculateInternationalParcelCost(string $serviceCode, string $countryCode, array $packages)
    {
        return Http::pool(function (Pool $pool) use ($serviceCode, $countryCode, $packages) {
            foreach ($packages as $package) {
                $pool->withHeaders([
                    'auth-key' => $this->apiKey
                ])->get('https://digitalapi.auspost.com.au/postage/parcel/international/calculate', [
                    'service_code' => $serviceCode,
                    'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
                    'country_code' => $countryCode,
                    'weight' => $package['weight']
                ]);
            }
        });
    }

    public function listDomesticParcelServices($toPostcode)
    {
        return Http::withHeaders([
            'auth-key' => $this->apiKey
        ])->get('https://digitalapi.auspost.com.au/postage/parcel/domestic/service', [
            'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
            'to_postcode' => $toPostcode,
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'weight' => 0.1
        ]);
    }

    public function listInternationalParcelServices($countryCode)
    {
        return Http::withHeaders([
            'auth-key' => $this->apiKey
        ])->get('https://digitalapi.auspost.com.au/postage/parcel/international/service', [
            'from_postcode' => config('simple-commerce-australia-post.from_postcode', null),
            'country_code' => $countryCode,
            'weight' => 0.1
        ]);
    }
}