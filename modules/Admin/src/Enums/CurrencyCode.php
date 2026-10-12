<?php

namespace DA\Admin\Enums;

enum CurrencyCode: string
{
    case USD = 'USD';
    case BDT = 'BDT';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case INR = 'INR';
    case AED = 'AED';
    case SGD = 'SGD';
    case SAR = 'SAR';
    case MYR = 'MYR';
    case AUD = 'AUD';

    private function info()
    {
        return match ($this) {
            self::USD => ['name' => 'US Dollar', 'symbol' => '$'],
            self::BDT => ['name' => 'Bangladeshi Taka', 'symbol' => '৳'],
            self::EUR => ['name' => 'Euro', 'symbol' => '€'],
            self::GBP => ['name' => 'British Pound', 'symbol' => '£'],
            self::INR => ['name' => 'Indian Rupee', 'symbol' => '₹'],
            self::AED => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
            self::SGD => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
            self::MYR => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
            self::AUD => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
        };
    }
}
