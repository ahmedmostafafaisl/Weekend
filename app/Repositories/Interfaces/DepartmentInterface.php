<?php

namespace App\Repositories\Interfaces;

interface DepartmentInterface
{
    public function all();
    public function find($id);
    public function getByUserId($userId);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
