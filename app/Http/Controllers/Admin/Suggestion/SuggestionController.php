<?php

namespace App\Http\Controllers\Admin\Suggestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Suggestion\StoreSuggestionRequest;
use App\Http\Requests\Suggestion\UpdateSuggestionRequest;
use App\Http\Resources\Suggestion\SuggestionResource;
use App\Models\User;
use App\Repositories\Interfaces\SuggestionRepositoryInterface;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    protected $repo;

    public function __construct(SuggestionRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $data = $this->repo->all();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => SuggestionResource::collection($data),

            ]);
        }
        $users = User::all(); // Fetch users for the dropdown

        return view('dashboard.admin.suggestions.index', ['suggestions' => $data, 'users' => $users]);

    }

    public function store(StoreSuggestionRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = auth()->id();

        $item = $this->repo->create($data);

        return $request->wantsJson()
                   ? new SuggestionResource($item)
                   : redirect()->route('admin.suggestions.index')->with('success', __('lang.suggestion_created_successfully_msg'));

    }

    public function show(Request $request, $id)
    {
        return $request->wantsJson()
           ? new SuggestionResource($this->repo->find($id))
           : view('dashboard.admin.suggestions.show', ['suggestion' => $this->repo->find($id)]);

    }

    public function update(UpdateSuggestionRequest $request, $id)
    {
        $this->repo->update($id, $request->validated());

        return $request->wantsJson()
                   ? new SuggestionResource($this->repo->find($id))
                   : redirect()->route('admin.suggestions.index')->with('success', __('lang.suggestion_updated_successfully_msg'));
    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        return $request->wantsJson()
                   ? response()->json(['message' => __('lang.deleted_successfully')])
                   : redirect()->route('admin.suggestions.index')->with('success', __('lang.suggestion_deleted_successfully_msg'));
    }

    public function mySuggestions(Request $request)
    {

        $user = $request->user();

        $data = $this->repo->getByUser($user->id);

        return response()->json([
            'data' => SuggestionResource::collection($data),

        ]);
    }
}
