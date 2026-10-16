<?php

namespace DA\Inventory\Concerns;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait OwnershipActionAuthorize
{
    public function authorizeAction(Model $model, string $action, ?User $actor = null): bool
    {
        $actor = $actor ?? auth()->user();

        if ($actor->is_super_admin) {
            return true;
        }

        if ($action === 'update') {
            $auhorize = $actor->hasPermission(Permission::OnlyCreatorUpdate) && $model->created_by !== $actor->id;

            if ($auhorize) {
                return false;
            }

            return true;
        }

        if ($action === 'delete') {
            $authorize = $actor->hasPermission(Permission::OnlyCreatorDelete) && $model->created_by !== $actor->id;

            if ($authorize) {
                return false;
            }

            return true;
        }

        return false;
    }
}
