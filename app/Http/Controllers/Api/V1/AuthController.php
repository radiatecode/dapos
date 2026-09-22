<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginUser $login): JsonResponse
    {
        try {
            $result = $login->handle($request->toDTO());
        } catch (ValidationException $exception) {
            RateLimiter::hit($request->throttleKey());

            throw $exception;
        }

        RateLimiter::clear($request->throttleKey());

        return response()->json([
            'data' => [
                'token' => $result->token,
                'token_type' => $result->tokenType,
                'user' => UserResource::make($result->user),
            ],
        ]);
    }

    public function logout(Request $request, LogoutUser $logout): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $logout->handle($user);
        }

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        assert($user instanceof User);

        return UserResource::make($user->loadMissing('roles'));
    }
}
