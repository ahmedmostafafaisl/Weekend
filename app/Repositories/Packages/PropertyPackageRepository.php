<?php

namespace App\Repositories\Packages;



use App\Models\AdPackage;
use App\Models\PropertyPackage;
use App\Repositories\Interfaces\PropertyPackageInterface;

class PropertyPackageRepository implements PropertyPackageInterface
{
    public function all()
    {
        return PropertyPackage::all();
    }

    public function find($id)
    {
        return PropertyPackage::findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $path = public_path("Packages/Property_packages");

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $imageName);

            // Save the relative path to DB
            $data['image'] = "Packages/Property_packages/{$imageName}";
        }
        return PropertyPackage::create($data);
    }

    public function update($id, array $data)
    {
        $package = $this->find($id);
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $path = public_path("Packages/Property_packages");

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $imageName);

            // Save the relative path to DB
            $data['image'] = "Packages/Property_packages/{$imageName}";
        }
        $package->update($data);
        return $package;
    }

    public function delete($id)
    {
        return PropertyPackage::destroy($id);
    }

    public function getAllPackages(): array
    {
        return [
            'property_packages' => PropertyPackage::where('status', 'active')->get(),
            'ad_packages' => AdPackage::where('status', 'active')->get(),
        ];
    }
}
