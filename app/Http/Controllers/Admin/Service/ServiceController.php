<?php

namespace App\Http\Controllers\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Resources\Service\ServiceResource;
use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with('group')->orderBy('sort_order')->latest()->get();

        if ($request->wantsJson()) {
            return ServiceResource::collection($services);
        }

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

        return $request->wantsJson()
            ? new ServiceResource($service->fresh('group'))
            : redirect()->route('admin.services.index')->with('success', __('lang.service_created_successfully_msg'));
    }

    public function update(StoreServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        return $request->wantsJson()
            ? new ServiceResource($service->fresh('group'))
            : redirect()->route('admin.services.index')->with('success', __('lang.service_updated_successfully_msg'));
    }

    public function destroy(Request $request, Service $service)
    {
        $service->delete();

        return $request->wantsJson()
            ? response()->json(['message' => __('lang.service_deleted_successfully_msg')])
            : redirect()->route('admin.services.index')->with('success', __('lang.service_deleted_successfully_msg'));
    }
}
