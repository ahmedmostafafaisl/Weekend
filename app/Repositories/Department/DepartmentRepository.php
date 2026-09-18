<?php

namespace App\Repositories\Department;

use App\Models\Department;
use App\Repositories\Interfaces\DepartmentInterface;
use Illuminate\Support\Facades\Storage;

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
        $sakImage = $data['sak_image'] ?? null;
        unset($data['images'], $data['sak_image']);

        $department = Department::create($data);

        if ($sakImage) {
            $department->sak_image = $this->storeSakImage($department, $sakImage);
            $department->save();
        }

        if (! empty($images)) {
            $this->storeImages($department, $images);
        }

        return $department->load('images');
    }

    public function update($id, array $data)
    {
        $department = Department::findOrFail($id);
        $images = $data['images'] ?? [];
        $deletedImageIds = $data['deleted_image_ids'] ?? null;
        $sakImage = $data['sak_image'] ?? null;
        unset($data['images'], $data['deleted_image_ids'], $data['sak_image']);

        $department->update($data);

        if ($sakImage) {
            // Delete the old sak_image file from disk before replacing it
            if ($department->sak_image && file_exists(public_path($department->sak_image))) {
                @unlink(public_path($department->sak_image));
            }
            $department->sak_image = $this->storeSakImage($department, $sakImage);
            $department->save();
        }

        // Delete only the specific images listed, leaving every other
        // existing image completely untouched -- a genuine partial
        // update that omits deleted_image_ids entirely (null, not an
        // empty array) doesn't touch existing images at all.
        if (! empty($deletedImageIds)) {
            $department->images()
                ->whereIn('id', array_map('intval', (array) $deletedImageIds))
                ->get()
                ->each(function ($img) {
                    Storage::disk('public')->delete($img->image);
                    $img->delete();
                });
        }

        if (! empty($images)) {
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

    protected function storeSakImage(Department $department, $file): string
    {
        $path = public_path("department/{$department->type}/{$department->name}/sak_image");
        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $imageName = time().'_'.$file->getClientOriginalName();
        $file->move($path, $imageName);

        return "department/{$department->type}/{$department->name}/sak_image/{$imageName}";
    }

    protected function storeImages(Department $department, array $images): void
    {
        foreach ($images as $image) {
            $type = $department->type;
            $name = $department->name;

            $path = public_path("department/{$type}/{$department->id}");
            if (! file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time().'_'.$image->getClientOriginalName();
            $image->move($path, $imageName);

            $department->images()->create([
                'image' => "department/{$type}/{$department->id}/{$imageName}",
            ]);
        }
    }
}
