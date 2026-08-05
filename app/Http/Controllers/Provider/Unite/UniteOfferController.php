<?php

namespace App\Http\Controllers\Provider\Unite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unite\StoreUniteOfferRequest;
use App\Http\Resources\Unite\UniteOfferResource;
use App\Models\Unite;
use App\Repositories\Interfaces\UniteOfferInterface;
use Illuminate\Http\Request;

class UniteOfferController extends Controller
{
    protected $uniteOffer;

    public function __construct(UniteOfferInterface $uniteOffer)
    {
        $this->uniteOffer = $uniteOffer;
    }

    public function index(Request $request)
    {
        $offers = $this->uniteOffer->all();
        $unites = Unite::with('offers')->get()->groupBy('type');

        if ($request->wantsJson()) {
            $data = [];

            foreach ($unites as $type => $uniteGroup) {
                $data[] = [
                    'type' => $type,
                    'unites' => $uniteGroup->map(function ($unite) {
                        return [
                            'id' => $unite->id,
                            'name' => $unite->name,
                            'offers' => $unite->offers->map(function ($offer) {
                                return [
                                    'id' => $offer->id,
                                    'start' => $offer->start,
                                    'end' => $offer->end,
                                    'morning_price' => $offer->morning_price,
                                    'evening_price' => $offer->evening_price,
                                    'full_day_price' => $offer->full_day_price,
                                    'status' => $offer->status,
                                ];
                            }),
                        ];
                    }),
                ];
            }

            return response()->json(['data' => $data]);
        }

        return view('dashboard.web.unites.offers.index', compact('offers', 'unites'));
    }

    public function create()
    {
        $types = ['stadium', 'hall', 'lounge', 'camp'];
        $unites = Unite::all()->groupBy('type');

        return view('dashboard.web.unites.offers.create', compact('types', 'unites'));
    }

    public function store(StoreUniteOfferRequest $request)
    {
        $offer = $this->uniteOffer->create($request->validated());

        return request()->wantsJson()
            ? UniteOfferResource::collection($offer)
            : redirect()->route('unite_offers.index')->with('success', __('lang.offer_created_msg'));
    }

    public function show($id)
    {
        $offer = $this->uniteOffer->find($id);

        return request()->wantsJson()
            ? new UniteOfferResource($offer)
            : view('dashboard.web.unites.offers.show', compact('offer'));
    }

    public function findByUniteId($id)
    {
        $offers = $this->uniteOffer->findByUniteId($id);
        if ($offers->isEmpty()) {
            return response()->json(['message' => 'No offers found for this unite'], 404);
        }

        return request()->wantsJson()
            ? UniteOfferResource::collection($offers)
            : view('dashboard.web.unites.offers.show', compact('offers'));
    }

    public function edit($id)
    {
        $offer = $this->uniteOffer->find($id);

        return view('dashboard.web.unites.offers.edit', compact('offer'));
    }

    public function update(StoreUniteOfferRequest $request, $id)
    {
        $data = $request->validated();
        $data['unite_id'] = $data['unite_id'][0] ?? null;

        $offer = $this->uniteOffer->update($id, $data);

        return request()->wantsJson()
            ? new UniteOfferResource($offer)
            : redirect()->route('unite_offers.index')->with('success', __('lang.offer_updated_msg'));
    }

    public function destroy($id)
    {
        $this->uniteOffer->delete($id);

        return request()->wantsJson()
            ? response()->json(['message' => 'Deleted successfully'])
            : redirect()->route('unite_offers.index')->with('success', __('lang.offer_deleted_msg'));
    }
}
