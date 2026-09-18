<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = AppSetting::orderBy('key')->get();

        return $request->expectsJson()
            ? response()->json(['data' => $settings])
            : view('dashboard.admin.app-settings.index', compact('settings'));
    }

    public function create()
    {
        return view('dashboard.admin.app-settings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:app_settings,key'],
            'label_en' => ['required', 'string', 'max:255'],
            'label_ar' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = ! empty($data['is_active']);

        $setting = AppSetting::create($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $setting], 201);
        }

        return redirect()->route('admin.app-settings.index')
            ->with('success', __('lang.settings_created'));
    }

    public function show(AppSetting $appSetting, Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['data' => $appSetting])
            : view('dashboard.admin.app-settings.show', ['setting' => $appSetting]);
    }

    public function edit(AppSetting $appSetting)
    {
        return view('dashboard.admin.app-settings.edit', ['setting' => $appSetting]);
    }

    public function update(Request $request, AppSetting $appSetting)
    {
        $data = $request->validate([
            'label_en' => ['sometimes', 'required', 'string', 'max:255'],
            'label_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // key is immutable after creation — code that calls AppSetting::get('some_key')
        // depends on a stable key, so we never allow renaming it.
        $appSetting->update([
            'label_en' => $data['label_en'] ?? $appSetting->label_en,
            'label_ar' => $data['label_ar'] ?? $appSetting->label_ar,
            'is_active' => isset($data['is_active'])
                ? (bool) $data['is_active']
                : (! empty($request->input('is_active'))),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $appSetting->fresh()]);
        }

        return redirect()->route('admin.app-settings.index')
            ->with('success', __('lang.settings_updated'));
    }

    public function destroy(AppSetting $appSetting, Request $request)
    {
        $appSetting->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('lang.settings_deleted')]);
        }

        return redirect()->route('admin.app-settings.index')
            ->with('success', __('lang.settings_deleted'));
    }
}
