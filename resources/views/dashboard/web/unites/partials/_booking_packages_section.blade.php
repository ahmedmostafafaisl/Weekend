{{--
  Booking Packages Section — inline add/edit for create and edit unite forms.
  $unite    = Unite|null
  $services = Collection<Service> — active services for the included-services selector

  Genuinely universal across all 4 venue types (stadium, hall, lounge, camp),
  unlike every other section in this form which still branches by type —
  package booking is an optional add-on available to every venue equally.
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">🎁 {{ __('lang.booking_packages') }}</h6>
                <div class="text-muted small">{{ __('lang.booking_packages_subtitle') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addRow('booking-packages-body','booking-package-tpl')">+ {{ __('lang.add_package') }}</button>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="package_booking_enabled" value="1"
                   id="package_booking_enabled"
                   {{ old('package_booking_enabled', $unite->package_booking_enabled ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="package_booking_enabled">{{ __('lang.package_booking_enabled') }}</label>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.name') }}</th>
                        <th style="min-width:160px">{{ __('lang.booking_package_days') }}</th>
                        <th>{{ __('lang.th_start') }}</th>
                        <th>{{ __('lang.th_end') }}</th>
                        <th>{{ __('lang.th_price') }}</th>
                        <th style="min-width:160px">{{ __('lang.included_services') }}</th>
                        <th>{{ __('lang.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="booking-packages-body">
                    @if($unite)
                        @foreach($unite->bookingPackages as $i => $pkg)
                        <tr>
                            <td><input class="form-control form-control-sm" name="booking_packages[{{ $i }}][name]" value="{{ $pkg->name }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][day]">
                                    @foreach(['week_day'=>__('lang.weekday'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')] as $v=>$l)
                                        <option value="{{ $v }}" {{ $pkg->day === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="form-control form-control-sm" type="time" name="booking_packages[{{ $i }}][start_time]" value="{{ $pkg->start_time }}"></td>
                            <td><input class="form-control form-control-sm" type="time" name="booking_packages[{{ $i }}][end_time]" value="{{ $pkg->end_time }}"></td>
                            <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="booking_packages[{{ $i }}][price]" value="{{ $pkg->price }}"></td>
                            <td>
                                @php($selectedServiceIds = $pkg->services->pluck('id')->all())
                                <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][service_ids][]" multiple size="3">
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}" {{ in_array($service->id, $selectedServiceIds) ? 'selected' : '' }}>{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="booking_packages[{{ $i }}][status]">
                                    <option value="active" {{ $pkg->status === 'active' ? 'selected' : '' }}>{{ __('lang.active') }}</option>
                                    <option value="inactive" {{ $pkg->status === 'inactive' ? 'selected' : '' }}>{{ __('lang.inactive') }}</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="booking-package-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="booking_packages[__I__][name]"></td>
        <td>
            <select class="form-select form-select-sm" name="booking_packages[__I__][day]">
                @foreach(['week_day'=>__('lang.weekday'),'thursday'=>__('lang.thursday'),'friday'=>__('lang.friday'),'saturday'=>__('lang.saturday')] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </td>
        <td><input class="form-control form-control-sm" type="time" name="booking_packages[__I__][start_time]"></td>
        <td><input class="form-control form-control-sm" type="time" name="booking_packages[__I__][end_time]"></td>
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="booking_packages[__I__][price]"></td>
        <td>
            <select class="form-select form-select-sm" name="booking_packages[__I__][service_ids][]" multiple size="3">
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="booking_packages[__I__][status]">
                <option value="active">{{ __('lang.active') }}</option>
                <option value="inactive">{{ __('lang.inactive') }}</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>
