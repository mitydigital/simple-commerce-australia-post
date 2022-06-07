<?php

namespace MityDigital\SimpleCommerceAustraliaPost\Enums;

enum AustraliaPostLocation: string
{
    case DOMESTIC = 'domestic';
    case INTERNATIONAL = 'international';

    public function label(): string
    {
        return match ($this) {
            self::DOMESTIC => 'Domestic',
            self::INTERNATIONAL => 'International'
        };
    }
}
