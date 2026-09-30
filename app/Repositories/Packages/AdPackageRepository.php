<?php

namespace App\Repositories\Packages;

use App\Models\AdPackage;
use App\Repositories\Interfaces\AdPackageInterface;
use App\Support\SafeUpload;

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
            $data['image'] = SafeUpload::store($image, 'Packages/Ad_packages', SafeUpload::IMAGES, 'image');
        }

        return AdPackage::create($data);
    }

    public function update($id, array $data)
    {
        $ad = $this->find($id);
        if (isset($data['image']) && is_file($data['image'])) {
            $image = request()->file('image');
            $data['image'] = SafeUpload::store($image, 'Packages/Ad_packages', SafeUpload::IMAGES, 'image');
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
