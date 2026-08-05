@php $prefix = $prefix ?? 'add'; @endphp

<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.scope') }} <span class="text-danger">*</span></label>
    <div class="d-flex gap-4">
        @foreach(['all' => __('lang.all_venues'), 'types' => __('lang.by_venue_type'), 'unites' => __('lang.specific_venues')] as $val => $label)
        <div class="form-check">
            <input class="form-check-input scope-radio-{{ $prefix }}" type="radio"
                   name="scope_type"
                   id="{{ $prefix }}_scope_{{ $val }}"
                   value="{{ $val }}"
                   {{ $val === 'all' ? 'checked' : '' }}
                   onchange="toggleScopePanel_{{ $prefix }}(this.value)"
                   required>
            <label class="form-check-label" for="{{ $prefix }}_scope_{{ $val }}">{{ $label }}</label>
        </div>
        @endforeach
    </div>
</div>

{{-- By type --}}
<div id="scope-{{ $prefix }}-types" class="scope-panel-{{ $prefix }} d-none border rounded p-3 mb-3 bg-light">
    <div class="fw-semibold small mb-3">{{ __('lang.select_venue_types') }}</div>
    <div class="d-flex gap-4 flex-wrap">
        @foreach(['stadium' => '🏟️ '.__('lang.stadium'), 'hall' => '🏛️ '.__('lang.hall'), 'lounge' => '🏠 '.__('lang.lounge'), 'camp' => '⛺ '.__('lang.camp')] as $type => $label)
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox"
                   name="types[]"
                   id="{{ $prefix }}_type_{{ $type }}"
                   value="{{ $type }}">
            <label class="form-check-label fw-semibold" for="{{ $prefix }}_type_{{ $type }}">
                {{ $label }}
            </label>
        </div>
        @endforeach
    </div>
</div>

{{-- Specific venues --}}
<div id="scope-{{ $prefix }}-unites" class="scope-panel-{{ $prefix }} d-none border rounded p-3 mb-3">
    <div class="fw-semibold small mb-2">{{ __('lang.select_specific_venues') }}</div>
    <input type="text" class="form-control form-control-sm mb-2"
           placeholder="{{ __('lang.filter_venues') }}"
           oninput="filterUnites_{{ $prefix }}(this.value)">
    <div id="{{ $prefix }}-unites-list" style="max-height:260px;overflow-y:auto">
        @php $grouped = $unites->groupBy('type'); @endphp
        @foreach($grouped as $type => $typeUnites)
        <div class="mb-3">
            <div class="text-uppercase fw-semibold text-muted mb-1"
                 style="font-size:10px;letter-spacing:.06em">
                {{ ucfirst($type) }}
            </div>
            <div class="row g-1">
                @foreach($typeUnites as $u)
                <div class="col-6 unites-item">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="unite_ids[]"
                               id="{{ $prefix }}_unite_{{ $u->id }}"
                               value="{{ $u->id }}">
                        <label class="form-check-label small"
                               for="{{ $prefix }}_unite_{{ $u->id }}">
                            {{ $u->name }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
function toggleScopePanel_{{ $prefix }}(val) {
    document.querySelectorAll('.scope-panel-{{ $prefix }}').forEach(function(el) {
        el.classList.add('d-none');
    });
    var panel = document.getElementById('scope-{{ $prefix }}-' + val);
    if (panel) panel.classList.remove('d-none');
}

function filterUnites_{{ $prefix }}(term) {
    term = term.toLowerCase();
    document.querySelectorAll('#{{ $prefix }}-unites-list .unites-item').forEach(function(item) {
        item.style.display = item.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

// Run on DOM ready to apply the current radio state (handles edit modal pre-selection)
(function() {
    function init() {
        var checked = document.querySelector('.scope-radio-{{ $prefix }}:checked');
        if (checked) toggleScopePanel_{{ $prefix }}(checked.value);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
