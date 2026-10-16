<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateUserPhoto
{
    public function handle(int $id, UploadedFile $photo): User
    {
        $user = User::queries()->findById($id);

        if (is_string($user->photo) && $user->photo !== '') {
            Storage::disk('public')->delete($user->photo);
        }

        $user->photo = $photo->store('users/photos', 'public');
        $user->save();

        return $user;
    }
}
