<?php

namespace App\Http\Controllers\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceGroupRequest;
use App\Http\Resources\Service\ServiceGroupResource;
use App\Models\ServiceGroup;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceGroupController extends Controller
{
    use HasVersionedCache;

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cacheKey = $this->versionedCacheKey('service_groups_index');

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                $groups = ServiceGroup::with('services')->orderBy('sort_order')->latest()->get();

                return ServiceGroupResource::collection($groups)->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        $groups = ServiceGroup::with('services')->orderBy('sort_order')->latest()->get();

        return view('dashboard.admin.service-groups.index', compact('groups'));
    }

    public function show(Request $request, ServiceGroup $service_group)
    {
        $service_group->load('services');

        if ($request->wantsJson()) {
            return new ServiceGroupResource($service_group);
        }

        return view('dashboard.admin.service-groups.show', compact('service_group'));
    }

    public function store(StoreServiceGroupRequest $request)
    {
        $group = ServiceGroup::create($request->validated());

        $this->bumpCacheVersion('service_groups_index');
        $this->bumpCacheVersion('services_index');

        return $request->wantsJson()
            ? new ServiceGroupResource($group)
            : redirect()->route('admin.service-groups.index')->with('success', __('lang.group_created_successfully_msg'));
    }

    public function update(StoreServiceGroupRequest $request, ServiceGroup $service_group)
    {
        $service_group->update($request->validated());

        $this->bumpCacheVersion('service_groups_index');
        $this->bumpCacheVersion('services_index');

        return $request->wantsJson()
            ? new ServiceGroupResource($service_group->fresh('services'))
            : redirect()->route('admin.service-groups.index')->with('success', __('lang.group_updated_successfully_msg'));
    }

    public function destroy(Request $request, ServiceGroup $service_group)
    {
        $service_group->delete();

        $this->bumpCacheVersion('service_groups_index');
        $this->bumpCacheVersion('services_index');

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.group_deleted_successfully_msg')])
            : redirect()->route('admin.service-groups.index')->with('success', __('lang.group_deleted_successfully_msg'));
    }
}
