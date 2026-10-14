<?php

namespace DA\Admin\Actions\Tenant;

use App\Models\User;
use DA\Admin\DTO\TenantSuperAdminDTO;
use DA\Admin\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateTenantSuperAdmin
{
    public function handle(Tenant $tenant, TenantSuperAdminDTO $dto): User
    {
        return DB::transaction(function () use ($tenant, $dto): User {
            $lockedTenant = Tenant::query()
                ->whereKey($tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyExists = User::query()
                ->where('tenant_id', $lockedTenant->id)
                ->where('is_super_admin', true)
                ->lockForUpdate()
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'email' => 'This tenant already has a super admin account.',
                ]);
            }

            $user = new User;
            $user->name = $dto->name;
            $user->email = $dto->email;
            $user->password = $dto->password;
            $user->tenant_id = $lockedTenant->id;
            $user->is_super_admin = true;
            $user->is_active = $this->shouldStartActive($lockedTenant);
            $user->save();

            if ((int) $user->tenant_id !== $lockedTenant->id || ! $user->is_super_admin) {
                throw new RuntimeException('Tenant super admin was not assigned to the selected tenant.');
            }

            return $user->refresh();
        });
    }

    private function shouldStartActive(Tenant $tenant): bool
    {
        return $tenant->isActive() && $tenant->subscription()->exists();
    }
}
