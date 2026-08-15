<?php

namespace App\Http\Controllers\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Resources\Service\ServiceResource;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    use HasVersionedCache;

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cacheKey = $this->versionedCacheKey('services_index');

            $payload = Cache::remember($cacheKey, now()->addHours(24), function () {
                $services = Service::with('group')->orderBy('sort_order')->latest()->get();

                return ServiceResource::collection($services)->resolve();
            });

            return response()->json(['data' => $payload]);
        }

        $services = Service::with('group')->orderBy('sort_order')->latest()->get();
        $groups = ServiceGroup::orderBy('label')->get();

        return view('dashboard.admin.services.index', compact('services', 'groups'));
    }

    public function show(Request $request, Service $service)
    {
        $service->load('group');

        if ($request->wantsJson()) {
            return new ServiceResource($service);
        }

        return view('dashboard.admin.services.show', compact('service'));
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated());

        $this->bumpCacheVersion('services_index');
        $this->bumpCacheVersion('service_groups_index');

        return $request->wantsJson()
            ? new ServiceResource($service->fresh('group'))
            : redirect()->route('admin.services.index')->with('success', __('lang.service_created_successfully_msg'));
    }

    public function update(StoreServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        $this->bumpCacheVersion('services_index');
        $this->bumpCacheVersion('service_groups_index');

        return $request->wantsJson()
            ? new ServiceResource($service->fresh('group'))
            : redirect()->route('admin.services.index')->with('success', __('lang.service_updated_successfully_msg'));
    }

    public function destroy(Request $request, Service $service)
    {
        $service->delete();

        $this->bumpCacheVersion('services_index');
        $this->bumpCacheVersion('service_groups_index');

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.service_deleted_successfully_msg')])
            : redirect()->route('admin.services.index')->with('success', __('lang.service_deleted_successfully_msg'));
    }
}
