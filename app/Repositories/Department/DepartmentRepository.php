<?php

namespace App\Repositories\Department;




use App\Models\Department;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Interfaces\DepartmentInterface;

class DepartmentRepository implements DepartmentInterface
{
    public function all()
    {
        return Department::with(['user', 'images'])->latest()->get();
    }

    public function find($id)
    {
        return Department::with(['user', 'images'])->findOrFail($id);
    }

    public function getByUserId($userId)
    {
        return Department::with('images')->where('user_id', $userId)->get();
    }


    public function create(array $data)
    {
        $images = $data['images'] ?? [];
        unset($data['images']);

        $department = Department::create($data);

        if (!empty($images)) {
            $this->storeImages($department, $images);
        }

        return $department->load('images');
    }


    public function update($id, array $data)
    {
        $department = Department::findOrFail($id);
        $images = $data['images'] ?? [];
        unset($data['images']);

        $department->update($data);

        if (!empty($images)) {
            // Delete old images
            foreach ($department->images as $img) {
                Storage::disk('public')->delete($img->image);
                $img->delete();
            }

            $this->storeImages($department, $images);
        }

        return $department->load('images');
    }

    public function delete($id)
    {
        $department = Department::findOrFail($id);

        foreach ($department->images as $img) {
            Storage::disk('public')->delete($img->image);
            $img->delete();
        }

        return $department->delete();
    }

    protected function storeImages(Department $department, array $images): void
    {
        foreach ($images as $image) {
            $type = $department->type;
            $name = $department->name;

            $path = public_path("department/{$type}/{$name}");
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $imageName);

            $department->images()->create([
                'image' => "department/{$type}/{$name}/{$imageName}",
            ]);
        }
    }
}
