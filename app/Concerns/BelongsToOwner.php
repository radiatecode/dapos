<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOwner
{
    // public static function bootBelongsToOwner()
    // {
    //     if (auth()->check()) {
    //         static::creating(function (Model $model) {
    //             $model->created_by = auth()->id();
    //         });

    //         static::updating(function (Model $model) {
    //             $model->updated_by = auth()->id();
    //         });
    //     }
    // }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
