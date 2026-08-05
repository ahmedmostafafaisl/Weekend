<?php

// app/Repositories/StadiumType/StadiumTypeRepository.php

namespace App\Repositories\StadiumType;

use App\Models\StadiumType;
use App\Repositories\Interfaces\StadiumTypeRepositoryInterface;

class StadiumTypeRepository implements StadiumTypeRepositoryInterface
{
    public function all()
    {
        return StadiumType::orderBy('created_at', 'desc')
            ->get();
    }

    public function find($id)
    {
        return StadiumType::findOrFail($id);
    }

    public function create(array $data): StadiumType
    {
        return StadiumType::create($data);
    }

    public function update($id, array $data): bool
    {
        return StadiumType::findOrFail($id)->update($data);
    }

    public function delete($id): bool
    {
        return StadiumType::findOrFail($id)->delete();
    }
}
