<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logoUrl = is_string($this->logo) && $this->logo !== ''
            ? Storage::disk('public')->url($this->logo)
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $logoUrl,
            'status' => $this->status->label(),
            'country' => $this->country,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'address' => $this->address_line_1.', '.$this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'phone' => $this->contact_person_phone,
        ];
    }
}
