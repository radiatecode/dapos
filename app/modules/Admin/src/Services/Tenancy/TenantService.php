<?php

namespace DA\Admin\Services\Tenancy;

use DA\Admin\Models\Tenant;
use Illuminate\Support\Arr;

class TenantService
{
    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFromValidated(array $data): array
    {
        $attributes = Arr::only($data, [
            'name',
            'slug',
            'legal_name',
            'status',
            'timezone',
            'currency',
        ]);

        if (isset($attributes['slug']) && $attributes['slug'] === '') {
            unset($attributes['slug']);
        }

        $this->mergeMappedAttributes($attributes, $data['address'] ?? null, [
            'line_1' => 'address_line_1',
            'line_2' => 'address_line_2',
            'city' => 'city',
            'state' => 'state',
            'postal_code' => 'postal_code',
            'country' => 'country',
        ]);

        $this->mergeMappedAttributes($attributes, $data['contact'] ?? null, [
            'name' => 'contact_name',
            'email' => 'contact_email',
            'phone' => 'contact_phone',
            'website' => 'website',
        ]);

        if (array_key_exists('billing', $data) && is_array($data['billing'])) {
            $this->mergeMappedAttributes($attributes, $data['billing'], [
                'name' => 'billing_name',
                'email' => 'billing_email',
                'phone' => 'billing_phone',
                'tax_id' => 'tax_id',
            ]);

            $this->mergeMappedAttributes($attributes, $data['billing']['address'] ?? null, [
                'line_1' => 'billing_address_line_1',
                'line_2' => 'billing_address_line_2',
                'city' => 'billing_city',
                'state' => 'billing_state',
                'postal_code' => 'billing_postal_code',
                'country' => 'billing_country',
            ]);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|null  $group
     * @param  array<string, string>  $map
     */
    private function mergeMappedAttributes(array &$attributes, ?array $group, array $map): void
    {
        if ($group === null) {
            return;
        }

        foreach ($map as $input => $column) {
            if (array_key_exists($input, $group)) {
                $attributes[$column] = $group[$input];
            }
        }
    }
}
