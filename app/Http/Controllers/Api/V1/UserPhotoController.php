<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\UpdateUserPhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\UpdatePhotoRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UserPhotoController extends Controller
{
    public function update(UpdatePhotoRequest $request, User $user, UpdateUserPhoto $update): UserResource
    {
        $photo = $request->file('photo');
        assert($photo instanceof UploadedFile);

        return UserResource::make($update->handle($user, $photo));
    }
}
