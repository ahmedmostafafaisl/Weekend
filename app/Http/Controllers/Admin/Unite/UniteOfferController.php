<?php

namespace App\Http\Controllers\Admin\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\Concerns\AuthorizesUniteSubResource;
use App\Http\Requests\Unite\StoreUniteOfferRequest;
use App\Http\Resources\Unite\UniteOfferResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UniteOfferInterface;
use Illuminate\Http\Request;

class UniteOfferController extends Controller
{
    use AuthorizesUniteSubResource;

    public function __construct(
        protected UniteOfferInterface $repo
    ) {}

    public function index(Request $request, Unite $unite)
    {
        $offers = $this->repo->allByUnite($unite);

        if ($request->wantsJson()) {
            return UniteOfferResource::collection($offers);
        }

        return view('dashboard.admin.unite-offers.index', compact('unite', 'offers'));
    }

    public function show(Request $request, Unite $unite, int $offer)
    {
        $offer = $this->repo->findByUnite($unite, $offer);

        abort_unless($offer, 404);

        if ($request->wantsJson()) {
            return new UniteOfferResource($offer);
        }

        return view('dashboard.admin.unite-offers.show', compact('unite', 'offer'));
    }

    public function create(Unite $unite)
    {
        return view('dashboard.admin.unite-offers.create', compact('unite'));
    }

    public function store(StoreUniteOfferRequest $request, Unite $unite)
    {
        $offer = $this->repo->createForUnite($unite, $request->validated());

        return $request->wantsJson()
            ? new UniteOfferResource($offer)
            : redirect()->route('admin.unite-offers.index', $unite->id)
                ->with('success', __('lang.offer_created_successfully_msg'));
    }

    public function edit(Unite $unite, int $offer)
    {
        $offer = $this->repo->findByUnite($unite, $offer);

        abort_unless($offer, 404);

        return view('dashboard.admin.unite-offers.edit', compact('unite', 'offer'));
    }

    public function update(StoreUniteOfferRequest $request, Unite $unite, int $offer)
    {
        $offer = $this->repo->updateForUnite($unite, $offer, $request->validated());

        return $request->wantsJson()
            ? new UniteOfferResource($offer)
            : redirect()->route('admin.unite-offers.show', [$unite->id, $offer->id])
                ->with('success', __('lang.offer_updated_successfully_msg'));
    }

    public function destroy(Request $request, Unite $unite, int $offer)
    {
        abort_unless(
            $this->userMayAccessUniteSubResource($request->user(), $unite, 'unites.delete'),
            403
        );

        $this->repo->deleteForUnite($unite, $offer);

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.offer_deleted_successfully_msg')])
            : redirect()->route('admin.unite-offers.index', $unite->id)
                ->with('success', __('lang.offer_deleted_successfully_msg'));
    }

    public function findByUniteId($id)
    {
        $unite = Unite::find($id);
        if (! $unite) {
            return response()->json(['message' => __('lang.unite_not_found')], 404);
        }
        $offers = $unite->offers;
        if ($offers->isEmpty()) {
            return response()->json(['message' => __('lang.no_offers_found_for_unite')], 404);
        }

        return request()->wantsJson()
            ? UniteOfferResource::collection($offers)
            : view('dashboard.web.unites.offers.show', compact('offers'));
    }
}
