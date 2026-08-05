<?php

namespace App\Repositories\Subscription;

use App\Models\Subscription;
use App\Repositories\Interfaces\SubscriptionInterface;

class SubscriptionRepository implements SubscriptionInterface
{
    public function all()
    {
        return Subscription::with(['user', 'adPackage', 'propertyPackage'])->get();
    }

    public function find($id)
    {
        return Subscription::with(['user', 'adPackage', 'propertyPackage'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Subscription::create($data);
    }

    public function update($id, array $data)
    {
        $subscription = $this->find($id);
        $subscription->update($data);
        return $subscription;
    }

    public function delete($id)
    {
        $subscription = $this->find($id);
        return $subscription->delete();
    }
}
