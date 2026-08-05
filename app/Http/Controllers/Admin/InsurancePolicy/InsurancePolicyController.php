<?php

namespace App\Http\Controllers\Admin\InsurancePolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsurancePolicy\StoreInsurancePolicyRequest;
use App\Http\Requests\InsurancePolicy\UpdateInsurancePolicyRequest;
use App\Http\Resources\InsurancePolicy\InsurancePolicyResource;
use App\Repositories\Interfaces\InsurancePolicyRepositoryInterface;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    protected $repo;

    public function __construct(InsurancePolicyRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {

        $data = $this->repo->all();
        if ($request->wantsJson()) {
            return response()->json([
                'data' => InsurancePolicyResource::collection($data),
            ]);
        }

        return view('dashboard.admin.insurance_policies.index', ['insurancePolicies' => $data]);

    }

    public function store(StoreInsurancePolicyRequest $request)
    {
        $data = $request->validated();

        $item = $this->repo->create($data);

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

        return $request->wantsJson()
                    ? new InsurancePolicyResource($this->repo->find($id))
                    : redirect()->route('admin.insurance_policies.index')->with('success', __('lang.insurance_policy_updated_successfully_msg'));

    }

    public function destroy(Request $request, $id)
    {
        $this->repo->delete($id);

        return $request->wantsJson()
                    ? response()->json(['message' => __('lang.deleted_successfully')])
                    : redirect()->route('admin.insurance_policies.index')->with('success', __('lang.insurance_policy_deleted_successfully_msg'));
    }
}
