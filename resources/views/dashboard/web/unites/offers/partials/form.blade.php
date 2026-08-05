<div class="mb-3">
    <label>Unite</label>
    <input type="text" class="form-control" value="{{ $offer->unite->name ?? 'N/A' }}" disabled>
    <input type="hidden" name="unite_id" value="{{ old('unite_id', $offer->unite_id ?? '') }}">
</div>

<div class="mb-3">
    <label>Start Date</label>
    <input type="date" name="start" value="{{ old('start', $offer->start ?? '') }}" class="form-control" required>
</div>
<div class="mb-3">
    <label>End Date</label>
    <input type="date" name="end" value="{{ old('end', $offer->end ?? '') }}" class="form-control" required>
</div>
<div class="mb-3">
    <label>Morning Price</label>
    <input type="number" step="0.01" name="morning_price"
        value="{{ old('morning_price', $offer->morning_price ?? '') }}" class="form-control">
</div>
<div class="mb-3">
    <label>Evening Price</label>
    <input type="number" step="0.01" name="evening_price"
        value="{{ old('evening_price', $offer->evening_price ?? '') }}" class="form-control">
</div>
<div class="mb-3">
    <label>Full Day Price</label>
    <input type="number" step="0.01" name="full_day_price"
        value="{{ old('full_day_price', $offer->full_day_price ?? '') }}" class="form-control">
</div>
<div class="mb-3">
    <label>Status</label>
    <select name="status" class="form-control">
        <option value="active" {{ old('status', $offer->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $offer->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
        </option>
    </select>
</div>
