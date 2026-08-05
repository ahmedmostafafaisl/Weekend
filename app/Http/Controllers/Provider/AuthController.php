<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\User\LoginRequest;
use App\Http\Requests\Auth\User\RegisterRequest;
use App\Http\Resources\Auth\User\UserResource;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected UserInterface $userRepo;

    public function __construct(UserInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->userRepo->register($request->validated());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $auth = $this->userRepo->login($request->validated());

        return response()->json([
            'user' => new UserResource($auth['user']),
            'token' => $auth['token'],
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(['message' => __('lang.logged_out_successfully')]);
    }

    // update fcm token
    public function updateFcmToken(Request $request)
    {
        $user = auth()->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => __('lang.fcm_token_updated_successfully')]);
    }
}
