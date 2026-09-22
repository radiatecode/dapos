<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateUserPhoto
{
    public function handle(User $user, UploadedFile $photo): User
    {
        if (is_string($user->photo) && $user->photo !== '') {
            Storage::disk('public')->delete($user->photo);
        }

        $user->photo = $photo->store('users/photos', 'public');
        $user->save();

        return $user->fresh(['roles']) ?? $user;
    }
}
