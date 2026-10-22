<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('role');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'photo' => $this->photo,
            'is_super_admin' => (bool) $this->is_super_admin,
            'currency' => $this->tenant?->currency ?? 'BDT',
            'photo_url' => is_string($this->photo) && $this->photo !== ''
                ? Storage::disk('public')->url($this->photo)
                : null,
            'role' => $this->when(
                $this->resource->getRelation('role') instanceof Role,
                fn () => RoleResource::make($this->resource->getRelation('role')),
            ),
            'tenant' => TenantResource::make($this->whenLoaded('tenant')),
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn (): array => $this->permissionKeys()->all(),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
