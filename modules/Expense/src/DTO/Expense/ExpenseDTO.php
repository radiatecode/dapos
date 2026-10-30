<?php

namespace DA\Expense\DTO\Expense;

use DA\Expense\Enums\ExpenseStatus;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ExpenseDTO extends Data
{
    public function __construct(
        public int $categoryId,
        public int $storeId,
        public ?string $description,
        public string $amount,
        public string $expenseDate,
        public ?UploadedFile $attachment,
        public ?ExpenseStatus $status = null,
    ) {}
}
