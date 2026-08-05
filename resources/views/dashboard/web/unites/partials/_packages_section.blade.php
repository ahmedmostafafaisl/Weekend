{{--
  Packages Section — inline add/edit for create and edit unite forms.
  $unite = Unite|null
--}}
<div class="card card-soft shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0">📦 {{ __('lang.packages') }}</h6>
                <div class="text-muted small">{{ __('lang.capacity_packages') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="addRow('packages-body','package-tpl')">+ {{ __('lang.add_package') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.name') }}</th>
                        <th>{{ __('lang.price') }} (SAR)</th>
                        <th>{{ __('lang.men_capacity') }}</th>
                        <th>{{ __('lang.women_capacity') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="packages-body">
                    @if($unite)
                        @foreach($unite->packages as $i => $pkg)
                        <tr>
                            <td><input class="form-control form-control-sm" name="packages[{{ $i }}][name]"           value="{{ $pkg->name }}"           placeholder="e.g. Silver Package" required></td>
                            <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="packages[{{ $i }}][price]"          value="{{ $pkg->price }}"          placeholder="0.00"></td>
                            <td><input class="form-control form-control-sm" type="number" min="0"           name="packages[{{ $i }}][men_capacity]"     value="{{ $pkg->men_capacity }}"   placeholder="0"></td>
                            <td><input class="form-control form-control-sm" type="number" min="0"           name="packages[{{ $i }}][women_capacity]"   value="{{ $pkg->women_capacity }}" placeholder="0"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="package-tpl">
    <tr>
        <td><input class="form-control form-control-sm" name="packages[__I__][name]"          placeholder="{{ __('lang.name') }}" required></td>
        <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="packages[__I__][price]"         placeholder="0.00"></td>
        <td><input class="form-control form-control-sm" type="number" min="0"           name="packages[__I__][men_capacity]"    placeholder="0"></td>
        <td><input class="form-control form-control-sm" type="number" min="0"           name="packages[__I__][women_capacity]"  placeholder="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="this.closest('tr').remove()">✕</button></td>
    </tr>
</template>
