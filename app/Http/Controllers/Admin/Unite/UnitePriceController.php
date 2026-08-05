<?php

namespace App\Http\Controllers\Admin\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\StoreUnitePriceRequest;
use App\Http\Resources\Unite\UnitePriceResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UnitePriceInterface;
use Illuminate\Http\Request;

class UnitePriceController extends Controller
{
    public function __construct(
        protected UnitePriceInterface $repo
    ) {}

    public function index(Request $request, Unite $unite)
    {
        $prices = $this->repo->allByUnite($unite);

        if ($request->wantsJson()) {
            return UnitePriceResource::collection($prices);
        }

        return view('dashboard.admin.unite-prices.index', compact('unite', 'prices'));
    }

    public function show(Request $request, Unite $unite, int $price)
    {
        $price = $this->repo->findByUnite($unite, $price);

        abort_unless($price, 404);

        if ($request->wantsJson()) {
            return new UnitePriceResource($price);
        }

        return view('dashboard.admin.unite-prices.show', compact('unite', 'price'));
    }

    public function create(Unite $unite)
    {
        return view('dashboard.admin.unite-prices.create', compact('unite'));
    }

    public function store(StoreUnitePriceRequest $request, Unite $unite)
    {
        $price = $this->repo->createForUnite($unite, $request->validated());

        return $request->wantsJson()
            ? new UnitePriceResource($price)
            : redirect()->route('admin.unite-prices.index', $unite->id)
                ->with('success', __('lang.price_created_successfully_msg'));
    }

    public function edit(Unite $unite, int $price)
    {
        $price = $this->repo->findByUnite($unite, $price);

        abort_unless($price, 404);

        return view('dashboard.admin.unite-prices.edit', compact('unite', 'price'));
    }

    public function update(StoreUnitePriceRequest $request, Unite $unite, int $price)
    {
        $price = $this->repo->updateForUnite($unite, $price, $request->validated());

        return $request->wantsJson()
            ? new UnitePriceResource($price)
            : redirect()->route('admin.unite-prices.show', [$unite->id, $price->id])
                ->with('success', __('lang.price_updated_successfully_msg'));
    }

    public function destroy(Request $request, Unite $unite, int $price)
    {
        $this->repo->deleteForUnite($unite, $price);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.price_deleted_successfully_msg')])
            : redirect()->route('admin.unite-prices.index', $unite->id)
                ->with('success', __('lang.price_deleted_successfully_msg'));
    }
}
