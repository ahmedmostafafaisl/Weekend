@extends('dashboard.admin.layouts.app')

@section('title', 'Weekend | ' . __('lang.app_settings'))

@section('content')
    @php $me = auth('admin')->user(); @endphp

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-1">{{ __('lang.app_settings') }}</h4>
            <div class="text-muted small">{{ __('lang.app_settings_subtitle') }}</div>
        </div>
        <a href="{{ route('admin.app-settings.create') }}" class="btn btn-accent">
            + {{ __('lang.add_setting') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ __('lang.app_settings_warning') }}</div>
    </div>

    <div class="card card-soft shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('lang.setting') }}</th>
                            <th>{{ __('lang.key') }}</th>
                            <th class="text-center" style="width:100px">{{ __('lang.enabled') }}</th>
                            <th class="text-end" style="width:140px">{{ __('lang.th_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settings as $setting)
                            <tr>
                                <td class="text-muted small">{{ $setting->id }}</td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ app()->getLocale() === 'ar' ? $setting->label_ar : $setting->label_en }}
                                    </div>
                                    <div class="text-muted small d-none d-md-block">
                                        {{ app()->getLocale() === 'ar' ? $setting->label_en : $setting->label_ar }}
                                    </div>
                                </td>
                                <td>
                                    <code class="small">{{ $setting->key }}</code>
                                </td>
                                <td class="text-center">
                                    @if($setting->is_active)
                                        <span class="badge bg-success">{{ __('lang.yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('lang.no') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.app-settings.edit', $setting) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        {{ __('lang.edit') }}
                                    </a>

                                    <form action="{{ route('admin.app-settings.destroy', $setting) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('{{ __('lang.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            {{ __('lang.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    {{ __('lang.no_settings_yet') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
