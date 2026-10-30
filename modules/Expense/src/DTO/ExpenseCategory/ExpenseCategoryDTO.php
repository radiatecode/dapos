<?php

namespace DA\Expense\DTO\ExpenseCategory;

use Spatie\LaravelData\Data;

class ExpenseCategoryDTO extends Data
{
    public function __construct(
        public string $categoryName,
        public bool $isActive,
    ) {}
}
