<?php

namespace App\Http\Controllers\Admin\InsurancePolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsurancePolicy\StoreInsurancePolicyRequest;
use App\Http\Requests\InsurancePolicy\UpdateInsurancePolicyRequest;
use App\Http\Resources\InsurancePolicy\InsurancePolicyResource;
use App\Repositories\Interfaces\InsurancePolicyRepositoryInterface;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InsurancePolicyController extends Controller
{
    use HasVersionedCache;

    protected $repo;

    public function __construct(InsurancePolicyRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cacheKey = $this->versionedCacheKey('insurance_policies_index');

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                return InsurancePolicyResource::collection($this->repo->all())->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        $data = $this->repo->all();

        return view('dashboard.admin.insurance_policies.index', ['insurancePolicies' => $data]);
    }

    public function store(StoreInsurancePolicyRequest $request)
    {
        $data = $request->validated();

        $item = $this->repo->create($data);

        $this->bumpCacheVersion('insurance_policies_index');

        return $request->wantsJson()
            ? new InsurancePolicyResource($item)
            : redirect()->route('admin.insurance_policies.index')->with('success', __('lang.insurance_policy_created_successfully_msg'));
    }

    public function show(Request $request, $id)
    {
        return $request->wantsJson()
            ? new InsurancePolicyResource($this->repo->find($id))
            : view('dashboard.admin.insurance_policies.show', ['insurancePolicy' => $this->repo->find($id)]);

    }

    public function update(UpdateInsurancePolicyRequest $request, $id)
    {
        $data = $request->validated();

        $this->repo->update($id, $data);

        $this->bumpCacheVersion('insurance_policies_index');

        return $request->wantsJson()
                    ? new InsurancePolicyResource($this->repo->find($id))
                    : redirect()->route('admin.insurance_policies.index')->with('success', __('lang.insurance_policy_updated_successfully_msg'));

    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        $this->bumpCacheVersion('insurance_policies_index');

        return $request->wantsJson()
                    ? response()->json(['message' => __('lang.deleted_successfully')])
                    : redirect()->route('admin.insurance_policies.index')->with('success', __('lang.insurance_policy_deleted_successfully_msg'));
    }
}
