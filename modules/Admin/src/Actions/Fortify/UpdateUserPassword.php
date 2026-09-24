<?php

namespace DA\Admin\Actions\Fortify;

use DA\Admin\Models\AdminUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateUserPassword
{
    use PasswordValidationRules;

    /**
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(AdminUser $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:admin'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
