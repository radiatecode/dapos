<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Fortify\UpdateUserPassword;
use DA\Admin\Actions\Fortify\UpdateUserProfileInformation;
use DA\Admin\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof AdminUser, 403);

        return view('admin::profile.show', [
            'user' => $admin,
        ]);
    }

    public function update(Request $request, UpdateUserProfileInformation $action): RedirectResponse
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof AdminUser, 403);

        $action->update($admin, $request->only(['name', 'email']));

        return back();
    }

    public function updatePassword(Request $request, UpdateUserPassword $action): RedirectResponse
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof AdminUser, 403);

        $action->update($admin, $request->only(['current_password', 'password', 'password_confirmation']));

        return back();
    }
}
