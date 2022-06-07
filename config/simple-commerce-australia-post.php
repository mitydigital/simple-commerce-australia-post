<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dimension Field Mappings
    |--------------------------------------------------------------------------
    |
    | Australia Post's API requires the dimensions and weight of each item.
    | to be able to calculate anything.
    |
    | The mapping keys must remain - but the values for each are the field handle
    | to collect the dimension attributes.
    |
    | The default config looks for a field called "height". If you had a different
    | field, such as "package_height", you would change the config below to be:
    |    'height' => 'package_height'
    |
    | You can also have a field (use the AP Shipping Methods fieldtype) to make
    | it easy to exclude specific products from calculations.
    |
    */

    'mappings' => [
        /* dimensions */
        'height' => 'height',
        'length' => 'length',
        'width' => 'width',

        /* exclude from calculations */
        'exclude_from_calculations' => 'exclude_from_calculations',

        /* weight */
        'weight' => 'weight',
    ],


    /*
    |--------------------------------------------------------------------------
    | From Postcode
    |--------------------------------------------------------------------------
    |
    | The "from" postcode used for all Australia Post requests.
    |
    */

    'from_postcode' => '',


    /*
    |--------------------------------------------------------------------------
    | Australia Post Postage Assessment Calculator (PAC) API Key
    |--------------------------------------------------------------------------
    |
    | You need an API key to access the Australia Post Postage Assessment
    | Calculator (PAC). You can register for a key at:
    |
    | https://developers.auspost.com.au/apis/pacpcs-registration
    |
    */

    'api_key' => env('AUSTRALIA_POST_PAC_KEY', null)

];
