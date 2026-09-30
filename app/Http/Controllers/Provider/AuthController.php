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
use Illuminate\Support\Facades\Log;
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
        // Enforce rate limiting BEFORE credential check so an attacker
        // cannot enumerate valid emails by observing whether the rate
        // limit fires before or after the credential error.
        $request->ensureIsNotRateLimited();

        try {
            $auth = $this->userRepo->login($request->validated());
        } catch (\Throwable $e) {
            // Record the failed attempt against this email + IP pair.
            $request->hitRateLimit();
            throw $e;
        }

        // Successful login — clear the counter so legitimate users are
        // not locked out after a previous failed attempt.
        $request->authenticate(); // clears the rate-limit counter

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

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => __('lang.fcm_token_updated_successfully')]);
    }

    /**
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('ResetPasswordNotification mail failed', [
                'email' => $request->email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // The raw mail exception (SMTP host, port, username, TLS details)
            // was returned to any unauthenticated caller as 'mail_error', with
            // a contradictory "link sent" message on an HTTP 500. It is logged
            // above; the client gets an honest, generic message.
            return response()->json([
                'message' => __('lang.password_reset_mail_failed'),
            ], 503);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __('lang.password_reset_link_sent')]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => __('lang.password_reset_throttled')], 429);
        }

        // INVALID_USER — no account for this email.
        // Do NOT leak the raw $status constant; the message is enough.
        return response()->json(['message' => __('lang.email_not_found')], 422);
    }

    /**
     * POST /api/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
