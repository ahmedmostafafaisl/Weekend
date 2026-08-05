<?php

namespace App\Repositories\Interfaces;

interface AdInterface
{
    public function all();

    public function allActive();

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    public function getAdsForUser($userId);

    public function getAdsGroupedByUser($userId);

    public function markAsSeen($adId, $userId);

    public function activate($id, $userId = null);
}
