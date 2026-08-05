@extends('dashboard.admin.layouts.app')
@section('title', 'Weekend | Broadcast Notifications')

@push('css')
<style>
.user-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 8px 3px 10px;border-radius:20px;background:var(--color-background-secondary);border:.5px solid var(--color-border-secondary);font-size:12px;margin:2px}
.user-chip button{background:none;border:none;padding:0;line-height:1;cursor:pointer;color:var(--color-text-secondary);font-size:14px}
.user-chip button:hover{color:var(--color-text-primary)}
#user-dropdown{position:absolute;z-index:9999;background:var(--color-background-primary);border:.5px solid var(--color-border-secondary);border-radius:var(--border-radius-md);box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:240px;overflow-y:auto;width:100%}
.drop-item{padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:.5px solid var(--color-border-tertiary)}
.drop-item:last-child{border-bottom:none}
.drop-item:hover{background:var(--color-background-secondary)}
.drop-item small{color:var(--color-text-secondary)}
.badge-customer{background:#E6F1FB;color:#0C447C}
.badge-provider{background:#EAF3DE;color:#27500A}
</style>
@endpush

@section('content')
@php $me = auth('admin')->user(); @endphp

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>{{ __('lang.please_fix_the_following_errors') }}</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li class="small">{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
        {{ session('warning') }}
        @if(session('broadcast_errors'))
            <ul class="mb-0 mt-2 small">
                @foreach(session('broadcast_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        @if(session('broadcast_errors'))
            <ul class="mb-0 mt-2 small">
                @foreach(session('broadcast_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('lang.broadcast') }}</h4>
        <div class="text-muted small">{{ __('lang.broadcast_subtitle') }}</div>
    </div>
</div>

{{-- Audience stat cards --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>__('lang.all_users'),      'value'=>$stats['total'],     'color'=>'secondary', 'aud'=>'all'],
        ['label'=>__('lang.customers'),      'value'=>$stats['customers'], 'color'=>'primary',   'aud'=>'customers'],
        ['label'=>__('lang.providers'),      'value'=>$stats['providers'], 'color'=>'success',   'aud'=>'providers'],
        ['label'=>__('lang.with_fcm'), 'value'=>$stats['with_fcm'],  'color'=>'info',      'aud'=>'with_fcm'],
    ] as $sc)
    <div class="col-6 col-md-3">
        <div class="card card-soft shadow-sm text-center py-3" style="cursor:pointer" onclick="setAudience('{{ $sc['aud'] }}')">
            <div class="fw-bold fs-4 text-{{ $sc['color'] }}">{{ number_format($sc['value']) }}</div>
            <div class="text-muted small">{{ $sc['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-5">

    {{-- Compose form --}}
    <div class="col-lg-7">
        <div class="card card-soft shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.compose_notification') }}</h6>

                <form action="{{ route('admin.broadcast.send') }}" method="POST" id="broadcastForm">
                    @csrf

                    {{-- Audience radios --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.audience') }} <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            @foreach([
                                'all'       => __('lang.all_users', ['count' => $stats['total']]),
                                'customers' => __('lang.customers', ['count' => $stats['customers']]),
                                'providers' => __('lang.providers', ['count' => $stats['providers']]),
                                'with_fcm'  => __('lang.with_fcm', ['count' => $stats['with_fcm']]),
                                'specific'  => __('lang.specific_users'),
                            ] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="audience"
                                       id="aud_{{ $val }}" value="{{ $val }}"
                                       {{ $val === 'all' ? 'checked' : '' }}
                                       onchange="onAudienceChange('{{ $val }}')">
                                <label class="form-check-label" for="aud_{{ $val }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-text">Will send to <strong id="audience-count">{{ $stats['total'] }}</strong> user(s).</div>
                    </div>

                    {{-- Specific user picker --}}
                    <div id="specific-panel" class="d-none mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.select_users') }}</label>

                        {{-- Type filter --}}
                        <div class="d-flex gap-2 mb-2">
                            <select id="picker-type-filter" class="form-select form-select-sm" style="width:160px" onchange="renderUserList()">
                                <option value="">{{ __('lang.all_types') }}</option>
                                <option value="customer">{{ __('lang.customers_only') }}</option>
                                <option value="provider">{{ __('lang.providers_only') }}</option>
                            </select>
                            <input type="text" id="user-search-input" class="form-control form-control-sm"
                                   placeholder="{{ __('lang.search_name_email') }}" autocomplete="off"
                                   oninput="filterUserList(this.value)">
                        </div>

                        {{-- Selected chips --}}
                        <div id="selected-chips" class="mb-2 min-height-1"></div>

                        {{-- Dropdown list --}}
                        <div style="position:relative">
                            <div id="user-dropdown" class="d-none"></div>
                        </div>

                        {{-- Full user list --}}
                        <div class="border rounded p-2" style="max-height:220px;overflow-y:auto">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="text-muted small" id="list-count"></span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="selectAll()" style="font-size:11px">{{ __('lang.select_all') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="clearAll()" style="font-size:11px">Clear</button>
                                </div>
                            </div>
                            <div id="user-list"></div>
                        </div>

                        {{-- Hidden inputs --}}
                        <div id="user-id-inputs"></div>
                    </div>

                    {{-- Title --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="notif-title" class="form-control"
                               maxlength="100" required placeholder="{{ __('lang.notification_title_placeholder') }}"
                               oninput="updatePreview();document.getElementById('title-count').textContent=this.value.length+'/100'">
                        <div class="text-end"><span id="title-count" class="text-muted" style="font-size:11px">0/100</span></div>
                    </div>

                    {{-- Body --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('lang.message') }} <span class="text-danger">*</span></label>
                        <textarea name="body" id="notif-body" class="form-control" rows="3"
                                  maxlength="500" required placeholder="{{ __('lang.notification_body_placeholder') }}"
                                  oninput="updatePreview();document.getElementById('body-count').textContent=this.value.length+'/500'"></textarea>
                        <div class="text-end"><span id="body-count" class="text-muted" style="font-size:11px">0/500</span></div>
                    </div>

                    {{-- Optional --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('lang.promo_code') }} <span class="text-muted small">({{ __('lang.optional') }})</span></label>
                            <input type="text" name="promo_code" id="notif-promo"
                                   class="form-control text-uppercase font-monospace"
                                   maxlength="50" placeholder="{{ __('lang.promo_code_placeholder') }}"
                                   oninput="updatePreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('lang.action_url') }} <span class="text-muted small">({{ __('lang.optional') }})</span></label>
                            <input type="url" name="action_url" class="form-control" placeholder="{{ __('lang.action_url_placeholder') }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_mail" value="1" id="send_mail">
                            <label class="form-check-label" for="send_mail">
                                {{ __('lang.also_send_by_email') }}
                                <span class="text-muted small">({{ __('lang.one_email_per_user') }})</span>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-accent px-4"
                                onclick="this.disabled=true;this.innerHTML='Sending…';this.form.submit()">
                            {{ __('lang.send_notification') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#testModal">{{ __('lang.test_send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview + delivery summary --}}
    <div class="col-lg-5">
        <div class="card card-soft shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('lang.push_preview') }}</h6>
                <div class="border rounded p-3" style="background:#f8f9fa;min-height:90px">
                    <div class="d-flex align-items-start gap-2">
                        <div style="width:36px;height:36px;border-radius:8px;background:#6f00ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">🏟</div>
                        <div>
                            <div class="fw-semibold small" id="preview-title" style="color:#111">{{ __('lang.title_will_appear_here') }}</div>
                            <div class="small text-muted" id="preview-body">{{ __('lang.your_message_will_appear_here') }}</div>
                            <div id="preview-promo" class="d-none mt-1">
                                <span class="badge bg-light text-dark border font-monospace small" id="preview-promo-val"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-borderless mt-3 mb-0" style="font-size:12px">
                    <tr><td class="text-muted">{{ __('lang.database_record') }}</td><td class="text-end"><span class="badge bg-success">{{ __('lang.always') }}</span></td></tr>
                    <tr><td class="text-muted">{{ __('lang.fcm_push') }}</td><td class="text-end"><span class="badge bg-info text-dark">{{ $stats['with_fcm'] }} {{ __('lang.with_token') }}</span></td></tr>
                    <tr><td class="text-muted">{{ __('lang.email') }}</td><td class="text-end"><span class="badge bg-secondary">{{ __('lang.optional') }}</span></td></tr>
                    <tr><td class="text-muted">{{ __('lang.delivery') }}</td><td class="text-end"><span class="badge bg-success">{{ __('lang.synchronous') }}</span></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Full broadcast history --}}
<div class="card card-soft shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('lang.broadcast_history') }}</h6>
        @if($history->isEmpty())
            <div class="text-muted small">{{ __('lang.no_broadcasts_sent_yet') }}</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lang.title') }}</th>
                        <th>{{ __('lang.message') }}</th>
                        <th class="text-center">{{ __('lang.sent_to') }}</th>
                        <th class="text-center">{{ __('lang.read') }}</th>
                        <th class="text-center">{{ __('lang.read_percentage') }}</th>
                        <th>{{ __('lang.promo_code') }}</th>
                        <th>{{ __('lang.sent_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($history as $h)
                @php $pct = $h->sent_to > 0 ? round($h->read_count / $h->sent_to * 100) : 0; @endphp
                <tr>
                    <td class="fw-semibold small">{{ $h->title }}</td>
                    <td>
                        <span class="text-muted small" style="max-width:220px;display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                            {{ $h->body }}
                        </span>
                    </td>
                    <td class="text-center small fw-semibold">{{ number_format($h->sent_to) }}</td>
                    <td class="text-center small">{{ number_format($h->read_count) }}</td>
                    <td class="text-center">
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-grow-1" style="height:5px;border-radius:3px">
                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                            </div>
                            <span style="font-size:10px;color:var(--color-text-secondary);min-width:28px">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($h->promo_code && $h->promo_code !== 'null')
                            <span class="badge bg-light text-dark border font-monospace small">{{ $h->promo_code }}</span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ \Carbon\Carbon::parse($h->sent_at)->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Test Modal --}}
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lang.test_send') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">{{ __('lang.send_test_notification') }}</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lang.user_email') }}</label>
                    <input type="email" id="test-email" class="form-control" placeholder="customer1@example.com">
                </div>
                <div id="test-result" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lang.cancel') }}</button>
                <button type="button" class="btn btn-accent" id="test-send-btn" onclick="sendTest()">{{ __('lang.send_test') }}</button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
// ── All users from PHP ────────────────────────────────────────────────────────
const ALL_USERS = @json($allUsers);
let selectedIds = new Set();
let filteredUsers = [...ALL_USERS];

const audienceCounts = {
    all: {{ $stats['total'] }},
    customers: {{ $stats['customers'] }},
    providers: {{ $stats['providers'] }},
    with_fcm: {{ $stats['with_fcm'] }},
};

// ── Audience ──────────────────────────────────────────────────────────────────
function setAudience(val) {
    const el = document.getElementById('aud_' + val);
    if (el) { el.checked = true; onAudienceChange(val); }
}

function onAudienceChange(val) {
    const panel = document.getElementById('specific-panel');
    const countEl = document.getElementById('audience-count');
    if (val === 'specific') {
        panel.classList.remove('d-none');
        countEl.textContent = selectedIds.size;
        renderUserList();
    } else {
        panel.classList.add('d-none');
        countEl.textContent = (audienceCounts[val] ?? 0).toLocaleString();
    }
}

// ── User list rendering ───────────────────────────────────────────────────────
function renderUserList() {
    const typeFilter = document.getElementById('picker-type-filter').value;
    const term = (document.getElementById('user-search-input').value || '').toLowerCase();

    filteredUsers = ALL_USERS.filter(u => {
        const matchType = !typeFilter || u.type === typeFilter;
        const matchTerm = !term || u.name.toLowerCase().includes(term) || u.email.toLowerCase().includes(term);
        return matchType && matchTerm;
    });

    const list = document.getElementById('user-list');
    document.getElementById('list-count').textContent = filteredUsers.length + ' users shown';

    if (!filteredUsers.length) {
        list.innerHTML = '<div class="text-muted small p-2">No users match.</div>';
        return;
    }

    list.innerHTML = filteredUsers.map(u => `
        <div class="form-check px-2 py-1 border-bottom" style="border-color:var(--color-border-tertiary)!important">
            <input class="form-check-input user-checkbox" type="checkbox"
                   id="uc_${u.id}" value="${u.id}"
                   ${selectedIds.has(u.id) ? 'checked' : ''}
                   onchange="toggleUser(${u.id}, this.checked)">
            <label class="form-check-label small w-100" for="uc_${u.id}" style="cursor:pointer">
                <span class="fw-semibold">${escHtml(u.name)}</span>
                <span class="badge badge-${u.type} ms-1" style="font-size:9px">${u.type}</span>
                ${u.has_fcm ? '<span style="font-size:10px;color:#27500A">📱</span>' : ''}
                <br>
                <span class="text-muted" style="font-size:11px">${escHtml(u.email)}</span>
            </label>
        </div>
    `).join('');
}

function filterUserList(term) { renderUserList(); }

function toggleUser(id, checked) {
    if (checked) selectedIds.add(id);
    else selectedIds.delete(id);
    updateChips();
    updateHiddenInputs();
    document.getElementById('audience-count').textContent = selectedIds.size;
}

function selectAll() {
    filteredUsers.forEach(u => selectedIds.add(u.id));
    renderUserList();
    updateChips();
    updateHiddenInputs();
    document.getElementById('audience-count').textContent = selectedIds.size;
}

function clearAll() {
    selectedIds.clear();
    renderUserList();
    updateChips();
    updateHiddenInputs();
    document.getElementById('audience-count').textContent = 0;
}

// ── Chips ─────────────────────────────────────────────────────────────────────
function updateChips() {
    const container = document.getElementById('selected-chips');
    if (!selectedIds.size) { container.innerHTML = '<span class="text-muted small">No users selected</span>'; return; }
    container.innerHTML = [...selectedIds].map(id => {
        const u = ALL_USERS.find(x => x.id === id);
        if (!u) return '';
        return `<span class="user-chip">${escHtml(u.name)} <button type="button" onclick="toggleUser(${id}, false); document.getElementById('uc_${id}') && (document.getElementById('uc_${id}').checked = false)">×</button></span>`;
    }).join('');
}

// ── Hidden inputs for form submission ─────────────────────────────────────────
function updateHiddenInputs() {
    const container = document.getElementById('user-id-inputs');
    container.innerHTML = [...selectedIds].map(id =>
        `<input type="hidden" name="user_ids[]" value="${id}">`
    ).join('');
}

// ── Preview ───────────────────────────────────────────────────────────────────
function updatePreview() {
    const title = document.getElementById('notif-title').value.trim();
    const body  = document.getElementById('notif-body').value.trim();
    const promo = document.getElementById('notif-promo').value.trim().toUpperCase();
    document.getElementById('preview-title').textContent = title || 'Title will appear here';
    document.getElementById('preview-body').textContent  = body  || 'Your message will appear here';
    const promoEl = document.getElementById('preview-promo');
    if (promo) { document.getElementById('preview-promo-val').textContent = promo; promoEl.classList.remove('d-none'); }
    else        { promoEl.classList.add('d-none'); }
}

// ── Form submit guard ─────────────────────────────────────────────────────────
document.getElementById('broadcastForm').addEventListener('submit', function(e) {
    const audience = document.querySelector('[name="audience"]:checked')?.value;
    if (audience === 'specific' && selectedIds.size === 0) {
        e.preventDefault();
        alert('Please select at least one user.');
    }
});

// ── Test send ─────────────────────────────────────────────────────────────────
async function sendTest() {
    const email = document.getElementById('test-email').value.trim();
    const title = document.getElementById('notif-title').value.trim();
    const body  = document.getElementById('notif-body').value.trim();
    const promo = document.getElementById('notif-promo').value.trim();
    const mail  = document.getElementById('send_mail').checked;
    const result = document.getElementById('test-result');

    if (!email) { alert('Enter an email address.'); return; }
    if (!title) { alert('Enter a title first.'); return; }
    if (!body)  { alert('Enter a message first.'); return; }

    const btn = document.getElementById('test-send-btn');
    btn.disabled = true; btn.textContent = 'Sending…';
    result.className = ''; result.innerHTML = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        ?? document.querySelector('[name="_token"]')?.value ?? '';
        const res  = await fetch('{{ route("admin.broadcast.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ email, title, body, promo_code: promo || null, send_mail: mail ? 1 : 0 }),
        });
        const json = await res.json();
        result.className = json.success ? 'alert alert-success small d-block' : 'alert alert-danger small d-block';
        result.innerHTML = json.success
            ? `<strong>Sent to:</strong> ${json.sent_to?.name} (${json.sent_to?.type})<br>
               <strong>Channels:</strong> ${Object.entries(json.channels).filter(([,v])=>v).map(([k])=>k).join(', ')}<br>
               <strong>Has FCM:</strong> ${json.sent_to?.has_fcm ? 'Yes ✓' : 'No — push skipped'}`
            : (json.message ?? 'Send failed.');
    } catch(e) {
        result.className = 'alert alert-danger small d-block';
        result.textContent = 'Request failed: ' + e.message;
    } finally {
        btn.disabled = false; btn.textContent = 'Send test';
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
renderUserList();
updateChips();
</script>
@endpush

@endsection
