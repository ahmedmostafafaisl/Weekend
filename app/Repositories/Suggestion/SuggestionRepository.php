<?php

namespace App\Repositories\Suggestion;

use App\Models\Suggestion;
use App\Repositories\Interfaces\SuggestionRepositoryInterface;

class SuggestionRepository implements SuggestionRepositoryInterface
{
    public function all()
    {
        return Suggestion::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

    }

    public function find($id)
    {
        return Suggestion::with('user')->findOrFail($id);
    }

    public function create(array $data): Suggestion
    {
        return Suggestion::create($data);
    }

    public function update($id, array $data): bool
    {
        return Suggestion::findOrFail($id)->update($data);
    }

    public function delete($id): bool
    {
        return Suggestion::findOrFail($id)->delete();
    }

    public function getByUser($userId)
    {
        return Suggestion::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
