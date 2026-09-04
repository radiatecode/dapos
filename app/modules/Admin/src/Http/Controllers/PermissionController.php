<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Models\AdminPermission;
use DA\Admin\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof AdminUser, 403);

        $admin->loadMissing('adminRoles.permissions');

        return view('admin::permissions.index', [
            'user' => $admin,
            'roles' => $admin->adminRoles,
            'groupedPermissions' => AdminPermission::queries()->groupedByGroup(),
            'grantedKeys' => $admin->adminPermissionKeys(),
        ]);
    }
}
