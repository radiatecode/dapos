<?php

namespace DA\Inventory\DTO\Category;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class CategoryDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $code,
        public ?string $description,
        public ?int $parentId,
        public ?UploadedFile $image,
        public int $sortOrder,
        public bool $isActive,
    ) {}
}
