@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="form-group">
    <label>Server Name</label>
    <input type="text" name="server_name" class="form-control"
        value="{{ old('server_name', $server->server_name ?? '') }}" required>
</div>

<div class="form-group">
    <label>Host Name</label>
    <input type="text" name="host_name" class="form-control" value="{{ old('host_name', $server->host_name ?? '') }}"
        required>
</div>

<div class="form-group">
    <label>IP Address</label>
    <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $server->ip_address ?? '') }}"
        pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
        title="Enter a valid IPv4 address (e.g., 192.168.1.1)" required>
</div>

<div class="form-group">
    <label>Disk Space (GB)</label>
    <input type="number" step="0.01" name="disk_space" class="form-control"
        value="{{ old('disk_space', $server->disk_space ?? '') }}">
</div>

<div class="form-group">
    <label>Account</label>
    <input type="text" name="account" class="form-control" value="{{ old('account', $server->account ?? '') }}">
</div>

<div class="form-group">
    <label>Notes</label>
    <textarea name="notes" class="form-control">{{ old('notes', $server->notes ?? '') }}</textarea>
</div>

<div class="form-group">
    <label>Status</label>
    <select name="status" class="form-control" required>
        <option value="active" {{ old('status', $server->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $server->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
        </option>
    </select>
</div>

<div class="form-group">
    <label>Last Update</label>
    <input type="datetime-local" name="last_update" class="form-control"
        value="{{ old('last_update', isset($server) && $server->last_update ? $server->last_update->format('Y-m-d\TH:i') : '') }}">
</div>

<div class="form-group">
    <label for="time_zone">Time Zone</label>
    <select name="time_zone" class="form-control" required>
        <option value="">-- Select Time Zone --</option>
        @foreach ($timezones as $tz)
            <option value="{{ $tz }}" {{ old('time_zone', $server->time_zone ?? '') == $tz ? 'selected' : '' }}>
                {{ $tz }}
            </option>
        @endforeach
    </select>
</div>


<hr>
<h5>Server Settings</h5>

@foreach($settings as $setting)
    <div class="form-group">
        <label>{{ ucfirst(str_replace('_', ' ', $setting->name)) }}</label>
        <select name="settings[{{ $setting->id }}]" class="form-control" required>
            <option value="">-- Select --</option>
            @foreach($setting->values as $value)
                <option value="{{ $value->id }}" {{ old("settings.{$setting->id}", $selected[$setting->id] ?? '') == $value->id ? 'selected' : '' }}>
                    {{ $value->value }}
                </option>
            @endforeach
        </select>
    </div>
@endforeach

<button type="submit" class="btn btn-success mt-3">{{ $button }}</button>
<a href="{{ route('admin.servers.index') }}" class="btn btn-secondary mt-3">Cancel</a>
