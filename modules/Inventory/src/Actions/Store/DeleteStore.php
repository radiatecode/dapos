<?php

namespace DA\Inventory\Actions\Store;

use DA\Inventory\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteStore
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $store = Store::queries()->findForTenant((int) $id);

            try {
                $store->delete();
            } catch (QueryException $exception) {
                if (! $this->isForeignKeyViolation($exception)) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'ids' => 'This store cannot be deleted because it is used by stock or a purchase.',
                ]);
            }
        }
    }

    private function isForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1451 || $driverCode === 19;
    }
}
