<?php

namespace App\Repositories\Packages;



use App\Models\AdPackage;
use App\Repositories\Interfaces\AdPackageInterface;

class AdPackageRepository implements AdPackageInterface
{
    public function all()
    {
        return AdPackage::all();
    }

    public function find($id)
    {
        return AdPackage::findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $path = public_path("Packages/Ad_packages");

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $imageName);

            // Save the relative path to DB
            $data['image'] = "Packages/Ad_packages/{$imageName}";
        }
        return AdPackage::create($data);
    }

    public function update($id, array $data)
    {
        $ad = $this->find($id);
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $path = public_path("Packages/Ad_packages");

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $imageName);

            // Save the relative path to DB
            $data['image'] = "Packages/Ad_packages/{$imageName}";
        }
        $ad->update($data);
        return $ad;
    }

    public function delete($id)
    {
        $ad = $this->find($id);
        return $ad->delete();
    }
}
