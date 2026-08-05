<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    /**
     * GET /api/profile
     * Returns the authenticated user's full profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * PUT /api/profile
     * Update any profile fields. All fields optional — send only what changed.
     *
     * Fields: name, email, phone, birth_date, nation, id_number,
     *         provider_type, organization_name, commercial_register_number,
     *         current_password + password + password_confirmation (for password change)
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', Rule::unique('users')->ignore($user->id)],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'nation' => ['sometimes', 'in:saudi,resident'],
            'gender' => ['sometimes', 'in:male,female'],
            'id_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'provider_type' => ['sometimes', 'in:individual,organization'],
            'organization_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'commercial_register_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            // Password change — only if current_password provided
            'current_password' => ['sometimes', 'required_with:password', 'string'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        // Verify current password before allowing password change
        if (isset($data['current_password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => __('lang.current_password_incorrect'),
                    'errors' => ['current_password' => ['Current password is incorrect.']],
                ], 422);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
        }

        // Mark email as unverified if it changed
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => __('lang.profile_updated_successfully_msg'),
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * POST /api/profile/photo
     * Upload or replace the user's profile photo.
     *
     * Body (multipart): photo (image file, max 5MB)
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        // Delete old photo from storage if it exists
        if ($user->photo) {
            $oldPath = storage_path('app/public/'.$user->photo);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $path = $request->file('photo')->store('users/photos', 'public_uploads');
        $user->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => __('lang.profile_photo_updated'),
            'photo_url' => asset('storage/'.$path),
        ]);
    }

    /**
     * DELETE /api/profile
     * Deactivate (soft-delete) the authenticated user's account.
     * Sets status = inactive and revokes all Sanctum tokens.
     * Does NOT hard-delete — admin can reactivate from dashboard.
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('lang.password_incorrect'),
                'errors' => ['password' => ['Password is incorrect.']],
            ], 422);
        }

        // Revoke all tokens so the user is fully logged out everywhere
        $user->tokens()->delete();

        // Mark as inactive — admin can reactivate; data is preserved
        $user->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => __('lang.account_deactivated_contact_support'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'type' => $user->type,
            'status' => $user->status,
            'nation' => $user->nation,
            'birth_date' => $user->birth_date,
            'id_number' => $user->id_number,
            'provider_type' => $user->provider_type,
            'organization_name' => $user->organization_name,
            'commercial_register_number' => $user->commercial_register_number,
            'photo_url' => $user->photo ? asset('storage/'.$user->photo) : null,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
