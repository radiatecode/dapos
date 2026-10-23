<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Role;
use App\Models\User;
use DA\Inventory\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class UsersResource extends JsonResource
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
            'photo_url' => is_string($this->photo) && $this->photo !== ''
                ? Storage::disk('public')->url($this->photo)
                : null,
            'role' => $this->when(
                $this->resource->getRelation('role') instanceof Role,
                fn () => RoleResource::make($this->resource->getRelation('role')),
            ),
            'stores' => $this->when(
                $this->relationLoaded('stores'),
                fn (): array => $this->stores->map(fn (Store $store): array => [
                    'id' => $store->id,
                    'name' => $store->name,
                ])->values()->all(),
            ),
            'manages_all_stores' => $this->when(
                $this->relationLoaded('stores'),
                fn (): bool => $this->stores->isEmpty(),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
