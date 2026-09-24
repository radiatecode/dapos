<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\TenantStatus;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class TenantDTO extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $name,
        public string $timezone,
        public string $currency,
        public string $address_line_1,
        public string $city,
        public string $state,
        public string $postal_code,
        public string $country,
        public string $contact_name,
        public string $contact_email,
        public string $contact_phone,
        public string $billing_name,
        public string $billing_email,
        public string $billing_phone,
        public string $billing_address_line_1,
        public string $billing_address_line_2,
        public string $billing_city,
        public string $billing_state,
        public string $billing_postal_code,
        public ?string $address_line_2,
        public ?string $website,
        public ?string $billing_country,
        public ?string $slug,
        public ?UploadedFile $logo,
        public TenantStatus $status = TenantStatus::Active,
    ) {
        //
    }
}
