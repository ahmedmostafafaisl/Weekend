@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Target Audience --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.target_audience') }} <span class="text-danger">*</span></label>
    <div class="d-flex gap-4">
        @foreach(['both' => '👥 '.__('lang.everyone'), 'men' => '👨 '.__('lang.men_only'), 'women' => '👩 '.__('lang.women_only')] as $val => $lbl)
        <div class="form-check">
            <input class="form-check-input" type="radio" name="target_audience"
                   id="aud_{{ $val }}" value="{{ $val }}" required
                   {{ old('target_audience', $ad->target_audience ?? 'both') === $val ? 'checked' : '' }}>
            <label class="form-check-label" for="aud_{{ $val }}">{{ $lbl }}</label>
        </div>
        @endforeach
    </div>
    @error('target_audience')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Show To --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.show_to') }} <span class="text-danger">*</span></label>
    <div class="d-flex gap-4">
        @foreach(['all' => '🌐 '.__('lang.all_users'), 'customers' => '🛒 '.__('lang.customers_only'), 'providers' => '🏪 '.__('lang.providers_only')] as $val => $lbl)
        <div class="form-check">
            <input class="form-check-input" type="radio" name="target_user_type"
                   id="utype_{{ $val }}" value="{{ $val }}" required
                   {{ old('target_user_type', $ad->target_user_type ?? 'all') === $val ? 'checked' : '' }}>
            <label class="form-check-label" for="utype_{{ $val }}">{{ $lbl }}</label>
        </div>
        @endforeach
    </div>
    @error('target_user_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Specific Users --}}
<div class="mb-3">
    <label class="form-label fw-semibold">
        {{ __('lang.specific_users') }}
        <span class="text-muted small">({{ __('lang.specific_users_help') }})</span>
    </label>
    <input type="text" id="user-search-input" class="form-control form-control-sm mb-2"
           placeholder="{{ __('lang.search_name_email_placeholder') }}" autocomplete="off">
    <div id="user-search-results" class="border rounded bg-white shadow-sm d-none"
         style="max-height:200px;overflow-y:auto;position:absolute;z-index:9999;width:calc(100% - 2rem)"></div>
    <div id="targeted-chips" class="d-flex flex-wrap gap-1 mb-1"></div>
    <div id="targeted-inputs"></div>
    @if(isset($ad) && method_exists($ad, 'targetUsers') && $ad->targetUsers->count())
        <div class="text-muted small">{{ __('lang.currently_targeted') }}: {{ $ad->targetUsers->pluck('name')->join(', ') }}</div>
    @endif
</div>

{{-- City --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.city') }} <span class="text-muted small">({{ __('lang.city_optional_help') }})</span></label>
    <input type="text" name="city" class="form-control"
           value="{{ old('city', $ad->city ?? '') }}" placeholder="{{ __('lang.city_example_placeholder') }}">
    @error('city')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Ad Owner --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.ad_owner') }} <span class="text-danger">*</span></label>
    <select name="user_id" id="user_id" class="form-select" required>
        <option value="">{{ __('lang.select_user_ellipsis') }}</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}"
                {{ old('user_id', $ad->user_id ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name }} — {{ $user->email }} ({{ ucfirst($user->type ?? '') }})
            </option>
        @endforeach
    </select>
    @error('user_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Title --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.title') }} <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control"
           value="{{ old('title', $ad->title ?? '') }}" required maxlength="255">
    @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Description --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.description') }}</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $ad->description ?? '') }}</textarea>
    @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Thumbnail --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.thumbnail') }}</label>
    <input type="file" name="thumbnail" class="form-control" accept="image/*">
    @error('thumbnail')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @if(isset($ad) && $ad->thumbnail)
        <div class="mt-2">
            <div class="small text-muted mb-1">{{ __('lang.current_thumbnail') }}:</div>
            <img src="{{ asset($ad->thumbnail) }}" alt="Thumbnail"
                 style="height:80px;width:120px;object-fit:cover;border-radius:6px"
                 class="border" onerror="this.style.display='none'">
        </div>
    @endif
</div>

{{-- Media --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('lang.media') }} <span class="text-muted small">({{ __('lang.media_help') }})</span></label>
    <input type="file" name="media[]" class="form-control" accept="image/*,video/*" multiple>
    @error('media')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('media.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    {{-- Show existing media safely --}}
    @if(isset($ad) && $ad->media)
    @php
        $raw = $ad->media;
        $mediaItems = is_array($raw) ? $raw
            : (is_string($raw) && str_starts_with(trim($raw), '[') ? (json_decode($raw, true) ?: [$raw])
            : [$raw]);
    @endphp
    @if(count(array_filter($mediaItems)))
        <div class="mt-2">
            <div class="small text-muted mb-1">{{ __('lang.current_media') }}:</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach(array_filter($mediaItems) as $m)
                    <img src="{{ asset($m) }}" style="height:80px;width:120px;object-fit:cover;border-radius:6px"
                         class="border" onerror="this.style.display='none'" alt="">
                @endforeach
            </div>
        </div>
    @endif
    @endif
</div>

{{-- Active --}}
<div class="mb-4">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $ad->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">{{ __('lang.active_immediately') }}</label>
    </div>
</div>

<button type="submit" class="btn btn-accent">{{ $button }}</button>
<a href="{{ route('admin.ads.index') }}" class="btn btn-secondary">{{ __('lang.cancel') }}</a>

{{-- Specific user search JS --}}
<script>
(function () {
    const input   = document.getElementById('user-search-input');
    const results = document.getElementById('user-search-results');
    const chips   = document.getElementById('targeted-chips');
    const inputs  = document.getElementById('targeted-inputs');
    const sel     = new Map();

    // Pre-fill on edit
    @if(isset($ad) && method_exists($ad, 'targetUsers') && $ad->targetUsers->count())
    @foreach($ad->targetUsers as $tu)
    _add({{ $tu->id }}, @json($tu->name), @json($tu->email));
    @endforeach
    @endif

    function _add(id, name, email) {
        if (sel.has(id)) return;
        sel.set(id, { name, email });
        _render();
    }
    window._removeTarget = function (id) { sel.delete(id); _render(); };

    function _render() {
        chips.innerHTML = [...sel.entries()].map(([id, u]) =>
            `<span class="badge bg-light text-dark border" style="font-size:12px;padding:5px 8px">
                ${_esc(u.name)}
                <button type="button" onclick="_removeTarget(${id})"
                        style="border:none;background:none;font-size:14px;line-height:1;cursor:pointer;margin-left:4px">×</button>
            </span>`
        ).join('');
        inputs.innerHTML = [...sel.keys()].map(id =>
            `<input type="hidden" name="target_users[]" value="${id}">`
        ).join('');
    }

    function _esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    if (!input) return;
    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { results.classList.add('d-none'); return; }
        timer = setTimeout(async () => {
            try {
                const r    = await fetch(`/api/admin/users/search?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await r.json();
                const list = Array.isArray(data) ? data : (data.data ?? []);
                if (!list.length) { results.classList.add('d-none'); return; }
                results.innerHTML = list.map(u =>
                    `<div style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee"
                          onmousedown="event.preventDefault();_addResult(${u.id},${JSON.stringify(u.name)},${JSON.stringify(u.email)})"
                          onmouseover="this.style.background='#f8f9fa'"
                          onmouseout="this.style.background=''">
                        <span class="fw-semibold">${_esc(u.name)}</span>
                        <span class="text-muted ms-2" style="font-size:11px">${_esc(u.email)}</span>
                        <span class="badge bg-secondary ms-1" style="font-size:10px">${u.type}</span>
                    </div>`
                ).join('');
                results.classList.remove('d-none');
            } catch(e) {}
        }, 300);
    });

    input.addEventListener('blur', () => setTimeout(() => results.classList.add('d-none'), 200));

    window._addResult = function (id, name, email) {
        _add(id, name, email);
        input.value = '';
        results.classList.add('d-none');
    };
})();
</script>
