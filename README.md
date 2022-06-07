# Australia Post Shipping Methods for Simple Commerce

<!-- statamic:hide -->

![Statamic 3.3+](https://img.shields.io/badge/Statamic-3.3+-FF269E?style=for-the-badge&link=https://statamic.com)
[![Australia Post Shipping Methods for Simple Commerce on Packagist](https://img.shields.io/packagist/v/mitydigital/simple-commerce-australia-post?style=for-the-badge)](https://packagist.org/packages/mitydigital/simple-commerce-australia-post/stats)

---

<!-- /statamic:hide -->

> The Australia Post Shipping Methods is an addon for Statamic 3.3+ and Simple Commerce 3+ provides a way to integrate
> with the Australia Post Postage Assessment Calculator.

[Simple Commerce](https://statamic.com/addons/double-three-digital/simple-commerce), by Duncan McClean, is an awesome
addon for e-commerce for Statamic.

Shipping Methods can be added to your site to give your customers different choices - and the purpose of this addon is
to make it easy for you to work with both Simple Commerce and
the [Australia Post Postage Assessment Calculator (PAC) API](https://developers.auspost.com.au/apis/pac/getting-started)
.

Using the Australia Post PAC does require additional product attributes: please read this entire document to understand
the requirements and configuration options.

This addon includes:

- a command to help generate Australia Post Shipping Methods for Simple Commerce
- commands to list the Australia Post Service Codes
- a fieldtype for excluding products from shipping calculations
- an internal service that translates an order of multiple products in to Australia Post-sized packages for postage
  quoting via the Australia Post Postage Assessment Calculator (PAC)

## Requirements

This addon is designed to work with:

- Statamic 3.3+
- Simple Commerce 3.0+
- PHP 8.1

## How to Install

This assumes you have Statamic and Simple Commerce installed and ready to go.

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and
click **install**, or run the following command from your project root:

``` bash
composer require mitydigital/simple-commerce-australia-post
```

You'll most likely need to make changes to the configuration file too, so publish that while you're at it:

```
php please vendor:publish --tag=simple-commerce-australia-post-config
```

To use the Australia Post PAC you will need an API key. You
can [register for an API key at Australia Post's website](https://developers.auspost.com.au/apis/pacpcs-registration).

When you have your API key, add it to your `.env` file:

```
AUSTRALIA_POST_PAC_KEY=YOUR_API_KEY
```

## The concept

Australia Post's Postage Assessment Calculator (PAC) allows you to get a quote for any of their services, including both
domestic (Australia) and international options.

For domestic packages, you will need to know your package dimensions and weight.

For international packages, you will need to know your package weight.

This addon attempts to bundle the items in a Simple Commerce order in to as few packages as possible while following the
Australia Post package size and volume restrictions. Each package gets a quote from the Australia Post PAC, and is
summed to give you a total cost for the shipping method.

Because Simple Commerce's shipping methods are simple and singular in nature, for each Australia Post service, you will
need to create a new Shipping Method for your Simple Commerce site. Don't worry: this addon has a command to help you do
this.

Each Shipping Method can be configured to work for both Domestic and International service codes. This means that you
could use "Parcel Post" with one Service Code for Domestic calculations, and one Service Code for International. You can
even disable domestic or international costings for a specific Shipping Method too.

For example, let's say you wanted to offer Parcel Post to both Domestic and International customers, and Express Post
for Australian customers only, you would need:

- A Parcel Post Shipping Method, with two Service Codes - one for Domestic and one for International
- An Express Post Shipping Method, with a Domestic Service Code only

It sounds more complicated than it is - and this addon will do the heavy lifting for you. You can use this addon's
command to make a new Shipping Method that will work with the Australia Post PAC, and run another command to find the
right service codes to use.

If anything goes awry while getting a shipping cost, the Shipping Method will be hidden from the user. This can include:

- exceeding Australia Post's restrictions
- not shipping that service code to the destination location
- general API connectivity errors (such as Australia Post being offline, invalid API key, etc)

Don't forget to update your Shipping Method selection view with appropriate handling of the case where there are no
shipping methods available.

### Australia Post restrictions

Australia Post has a number of restrictions on the size and weight of packages that the network can ship.

This addon is quite strict at following these, and will automatically hide the Shipping Method if your order's products
fail to fit within these restrictions.

Refer to
the [Australia Post Size and Weight Guidelines](https://auspost.com.au/business/shipping/check-sending-guidelines/size-weight-guidelines)
for full details.

## Configuration

When you have published the configuration file, you can find it in `/config/simple-commerce-australia-post.php`. This is
where you can:

- define your Blueprint field mappings
- define your "from" postcode, required for calculations to work

The comments in the config file should help you set up your configuration with ease.

## Creating an Australia Post Shipping Method

This addon will do the heavy lifting of calculating total package size and contacting the Australia Post PAC. This means
you need to use a special Shipping Method, and not Simple Commerce's standard.

To make an Australian Post Shipping Method, run:

```bash
php please make:australia-post-shipping-method YourAustraliaPostShippingMethod
```

This will create a new Shipping Method in your `app/ShippingMethods` folder.

Edit this file, as there are four things you will need to do:

1. Update the `name` string
2. Update the `description` string
3. Add a Domestic Service Code, or set to `false` to disable domestic calculations
4. Add an International Service Code, or set to `false` to disable international calculations

Don't forget to add your new Shipping Method
to [Simple Commerce's Shipping configuration](https://simple-commerce.duncanmcclean.com/shipping#content-configuration).

### Finding Australia Post Service Codes

Australia Post has many Service Codes - and it is up to you to determine which offers the right service for you and your
customers.

Two commands have been included to help you find the Service Codes for Domestic and International packages:

For Domestic package Service Codes:

```bash
php please scap:domestic-parcel-services {toPostcode}
```

You can provide any Australian postcode to get a list of services.

For International package Service Codes:

```bash
php please scap:international-parcel-services {countryCode}
```

You can provide a two-character country code to get a list of options available for that country.

Note that while these commands are looking at a specific postcode or country, but your customer's may pick another
postcode/country, if the Service Code is not available to their country, the Shipping Method will be hidden. It may be
a good idea to use simple or common Service Codes, or provide a non-Australia Post fixed-price shipping option.

## Product Blueprint requirements

For domestic calculations, the Australia Post PAC requires package dimensions and weight. For international
calculations, just the weight.

This means that every product in your Simple Commerce store will require these values.

**Without these values, your Australia Post Shipping Methods will not be presented to the user.**

You will need to add the following fields to your Product Blueprint:

- `width`, in centimetres (cm)
- `height`, in centimetres (cm)
- `length`, in centimetres (cm)
- `weight`, in kilograms (kg)

These can be called whatever you want them to be - you can use the `mappings` array in the configuration file to change
the field name that the calculator will look at to get each product's properties.

You can optionally use `exclude_from_calculations` too to allow you to select one or more Shipping Methods that the
product should be excluded from. The addon expects this to be an array of Shipping Methods (or an empty array), so best
to use a fieldtype like checkboxes to allow for multiple selections.

## Excluding a Product from calculations

There are times when you may wish to exclude a product from shipping calculations - for example, if you had a single
product that you wanted to ship for free, or be excluded from shipping calculations (such as a digital product).

This approach means the customer would still be charged for shipping some items in their order, but have others excluded
from the calculations.

Add the **AP Shipping Methods** fieldtype to your Products Blueprint, which will output a list of your registered
Shipping Methods that are made for this addon. When you edit a product, tick the box for the required Shipping Method to
have that product excluded from shipping calculations.

**Side note**: if you're looking for free shipping system-wide, a custom Shipping Method generated by Simple Commerce
itself with a $0.00 value may be a better approach.

**Important note**: this approach to selectively allowing some items to be exempt from the shipping calculations means
it applies to both domestic and international destinations - if your Shipping Method is configured for both, then it
would be excluded for both. If you only wanted to exclude a product from domestic shipping, you would need to create two
separate shipping methods - one for domestic only, and one for international only - so that you can mark the domestic as
being excluded, but international still gets calculated.

## Support

We love to share work like this, and help the community. However it does take time, effort and work.

The best thing you can do is [log an issue](../../issues).

Please try to be detailed when logging an issue, including a clear description of the problem, steps to reproduce the
issue, and any steps you may have tried or taken to overcome the issue too. This is an awesome first step to helping us
help you. So be awesome - it'll feel fantastic.

## Credits

- [Marty Friedel](https://github.com/martyf)

Requires:

- [Simple Commerce](https://statamic.com/addons/double-three-digital/simple-commerce)
  by [Duncan McClean](https://github.com/duncanmcclean)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
