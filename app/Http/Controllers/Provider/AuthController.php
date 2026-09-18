<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\User\LoginRequest;
use App\Http\Requests\Auth\User\RegisterRequest;
use App\Http\Resources\Auth\User\UserResource;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    /**
     * POST /api/forgot-password
     * Send a password-reset link to the given email address.
     * The link points to FRONTEND_URL/reset-password so the mobile app
     * or web frontend receives it and shows a "set new password" form.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __('lang.password_reset_link_sent'),
            ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => __('lang.password_reset_throttled'),
            ], 429);
        }

        // Password::INVALID_USER -- no account found for this email
        return response()->json([
            'message' => __('lang.email_not_found'),
            'status' => $status, // raw Laravel status for debugging
        ], 422);
    }

    /**
     * POST /api/reset-password
     * Validate the token and set the new password.
     * Invalidates all existing Sanctum tokens after a successful reset
     * so every previously-logged-in device is logged out.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing API tokens -- password changed,
                // every previously-issued token should be invalidated.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __('lang.password_reset_successfully'),
            ]);
        }

        // Token invalid, expired, or email mismatch
        return response()->json([
            'message' => __($status),
        ], 422);
    }
}
