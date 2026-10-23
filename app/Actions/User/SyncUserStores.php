<?php

namespace App\Actions\User;

use App\DTO\User\AssignStoresDTO;
use App\Models\User;
use App\Services\AllocatedStores;
use DA\Inventory\Models\Store;
use Illuminate\Validation\ValidationException;

class SyncUserStores
{
    public function handle(int $id, AssignStoresDTO $dto): User
    {
        $user = User::queries()->findById($id);

        return $this->sync($user, $dto->storeIds);
    }

    /**
     * Give the current user the store they just created when they are already limited to specific stores.
     */
    public function grantCreatedStore(int $storeId): void
    {
        $actor = auth()->user();
        $current = AllocatedStores::ids();

        if (! $actor instanceof User || $current === null) {
            return;
        }

        $this->replace($actor, array_values(array_unique([...$current, $storeId])));
    }

    /**
     * @param  list<int>  $storeIds
     */
    public function sync(User $user, array $storeIds): User
    {
        if ($user->is_super_admin) {
            throw ValidationException::withMessages([
                'store_ids' => 'The business owner always manages every store.',
            ]);
        }

        if ($storeIds !== []) {
            $existing = Store::queries()->idsExistingInTenant($storeIds, (int) $user->tenant_id);

            if (count($existing) !== count($storeIds)) {
                throw ValidationException::withMessages([
                    'store_ids' => 'The selected store is invalid.',
                ]);
            }

            $storeIds = $existing;
        }

        $actorStoreIds = AllocatedStores::ids();

        if ($actorStoreIds !== null) {
            if ($storeIds === []) {
                throw ValidationException::withMessages([
                    'store_ids' => 'Select at least one store. An administrator can remove the store limit.',
                ]);
            }

            if (array_diff($storeIds, $actorStoreIds) !== []) {
                throw ValidationException::withMessages([
                    'store_ids' => 'You can only allocate stores assigned to you.',
                ]);
            }

            $preserved = array_values(array_diff(
                User::queries()->allocatedStoreIds((int) $user->id, (int) $user->tenant_id),
                $actorStoreIds,
            ));
            $storeIds = array_values(array_unique([...$preserved, ...$storeIds]));
        }

        return $this->replace($user, $storeIds);
    }

    /**
     * @param  list<int>  $storeIds
     */
    private function replace(User $user, array $storeIds): User
    {
        $user->stores()->sync(collect($storeIds)->mapWithKeys(
            fn (int $storeId): array => [$storeId => ['tenant_id' => $user->tenant_id]],
        )->all());

        $actor = auth()->user();

        if ($actor instanceof User && (int) $actor->id === (int) $user->id) {
            AllocatedStores::forget($actor);
        }

        return $user->load(['role', 'stores']);
    }
}
