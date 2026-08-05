<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserRepository implements UserInterface
{
    private array $fileFields = [
        'photo',
        'front_identity',
        'back_identity',
        'sak_image',
        'commercial_register_image',
    ];

    public function register(array $data)
    {
        // Temporarily hash the password before creating user
        $data['password'] = bcrypt($data['password']);

        // Create the user first to get the ID
        $user = User::create($data);

        // Define fields to handle
        $imageFields = ['photo', 'front_identity', 'back_identity', 'sak_image', 'commercial_register_image'];

        foreach ($imageFields as $field) {
            if (request()->hasFile($field)) {
                $image = request()->file($field);
                $path = public_path("users/{$user->id}/{$field}");

                if (! file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                $imageName = time().'_'.$image->getClientOriginalName();
                $image->move($path, $imageName);

                // Save the relative path to DB
                $user->{$field} = "users/{$user->id}/{$field}/{$imageName}";
            }
        }

        $user->save(); // Save after setting image paths

        return $user;
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support to reactivate it.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Implement other methods like update, delete, etc. as needed
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): User
    {
        return User::findOrFail($id);
    }

    private function storeFile(?UploadedFile $file, string $dir = 'users'): ?string
    {
        if (! $file) {
            return null;
        }

        // storage/app/public/users/xxxx.ext
        return $file->store($dir, 'public');
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            // files
            foreach ($this->fileFields as $field) {
                if (! empty($data[$field]) && $data[$field] instanceof UploadedFile) {
                    $data[$field] = $this->storeFile($data[$field], 'users');
                } else {
                    unset($data[$field]);
                }
            }

            $data['password'] = Hash::make($data['password']);

            // customer => no provider fields
            if (($data['type'] ?? 'customer') === 'customer') {
                $data['provider_type'] = null;
                $data['commercial_register_number'] = null;
                $data['organization_name'] = null;
                $data['commercial_register_image'] = null;
                $data['commercial_name'] = null;
                $data['sak_image'] = null;
                $data['ownership'] = 0;
                $data['delegation'] = null;
            }

            return User::create($data);
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {

            $user = User::findOrFail($id);

            // files (replace if uploaded)
            foreach ($this->fileFields as $field) {
                if (! empty($data[$field]) && $data[$field] instanceof UploadedFile) {
                    $data[$field] = $this->storeFile($data[$field], 'users');
                } else {
                    unset($data[$field]); // important: don't overwrite with null
                }
            }

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            if (($data['type'] ?? $user->type) === 'customer') {
                $data['provider_type'] = null;
                $data['commercial_register_number'] = null;
                $data['organization_name'] = null;
                $data['commercial_register_image'] = null;
                $data['commercial_name'] = null;
                $data['sak_image'] = null;
                $data['ownership'] = 0;
                $data['delegation'] = null;
            }

            $user->update($data);

            return $user;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $user = $this->find($id);
            $user->delete();
        });
    }
}
