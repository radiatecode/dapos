<?php

namespace DA\Inventory\DTO\Brand;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class BrandDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $code,
        public ?string $description,
        public ?UploadedFile $logo,
        public bool $isActive,
    ) {}
}
