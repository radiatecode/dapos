<?php

namespace DA\Inventory\Actions\Supplier;

use DA\Inventory\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteSupplier
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $supplier = Supplier::queries()->findForTenant((int) $id);

            try {
                $supplier->delete();
            } catch (QueryException $exception) {
                if (! $this->isForeignKeyViolation($exception)) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'ids' => 'This supplier cannot be deleted because it is used on a purchase.',
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
