<?php

namespace App\Http\Controllers\Admin\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\StoreUniteSlotRequest;
use App\Http\Resources\Unite\UniteSlotResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UniteSlotInterface;
use Illuminate\Http\Request;

class UniteSlotController extends Controller
{
    public function __construct(
        protected UniteSlotInterface $repo
    ) {}

    public function index(Request $request, Unite $unite)
    {
        $slots = $this->repo->allByUnite($unite);

        if ($request->wantsJson()) {
            return UniteSlotResource::collection($slots);
        }

        return view('dashboard.admin.unite-slots.index', compact('unite', 'slots'));
    }

    public function show(Request $request, Unite $unite, int $slot)
    {
        $slot = $this->repo->findByUnite($unite, $slot);

        abort_unless($slot, 404);

        if ($request->wantsJson()) {
            return new UniteSlotResource($slot);
        }

        return view('dashboard.admin.unite-slots.show', compact('unite', 'slot'));
    }

    public function create(Unite $unite)
    {
        return view('dashboard.admin.unite-slots.create', compact('unite'));
    }

    public function store(StoreUniteSlotRequest $request, Unite $unite)
    {
        $slot = $this->repo->createForUnite($unite, $request->validated());

        return $request->wantsJson()
            ? new UniteSlotResource($slot)
            : redirect()->route('admin.unite-slots.index', $unite->id)
                ->with('success', __('lang.slot_created_successfully_msg'));
    }

    public function edit(Unite $unite, int $slot)
    {
        $slot = $this->repo->findByUnite($unite, $slot);

        abort_unless($slot, 404);

        return view('dashboard.admin.unite-slots.edit', compact('unite', 'slot'));
    }

    public function update(StoreUniteSlotRequest $request, Unite $unite, int $slot)
    {
        $slot = $this->repo->updateForUnite($unite, $slot, $request->validated());

        return $request->wantsJson()
            ? new UniteSlotResource($slot)
            : redirect()->route('admin.unite-slots.show', [$unite->id, $slot->id])
                ->with('success', __('lang.slot_updated_successfully_msg'));
    }

    public function destroy(Request $request, Unite $unite, int $slot)
    {
        $this->repo->deleteForUnite($unite, $slot);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.slot_deleted_successfully_msg')])
            : redirect()->route('admin.unite-slots.index', $unite->id)
                ->with('success', __('lang.slot_deleted_successfully_msg'));
    }

    public function availabilityAndPrices(Request $request, Unite $unite)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $result = $this->repo->getAvailabilityAndPrices(
            $unite,
            $data['start_date'],
            $data['end_date'] ?? null
        );

        return response()->json([
            'message' => __('lang.availability_prices_fetched'),
            'data' => $result,
        ]);
    }
}
