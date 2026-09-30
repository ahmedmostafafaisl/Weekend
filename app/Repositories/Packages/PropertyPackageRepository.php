<?php

namespace App\Repositories\Packages;

use App\Models\AdPackage;
use App\Models\PropertyPackage;
use App\Repositories\Interfaces\PropertyPackageInterface;
use App\Support\SafeUpload;

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
            $data['image'] = SafeUpload::store($image, 'Packages/Property_packages', SafeUpload::IMAGES, 'image');
        }

        return PropertyPackage::create($data);
    }

    public function update($id, array $data)
    {
        $package = $this->find($id);
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $data['image'] = SafeUpload::store($image, 'Packages/Property_packages', SafeUpload::IMAGES, 'image');
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
