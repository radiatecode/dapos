<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PermissionResource;
use App\Services\PermissionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function index(PermissionService $permissions): AnonymousResourceCollection
    {
        return PermissionResource::collection($permissions->catalog());
    }
}
