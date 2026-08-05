<div class="form-group">
    <label>Features</label>
    <div id="features-wrapper">
        @php $features = $unite->features ?? [null]; @endphp
        @foreach($features as $index => $feature)
            <div class="row mb-2">
                <div class="col">
                    <input type="text" name="features[{{ $index }}][name]" class="form-control" placeholder="Name"
                        value="{{ $feature->name ?? '' }}">
                </div>
                <div class="col">
                    <input type="text" name="features[{{ $index }}][description]" class="form-control"
                        placeholder="Description" value="{{ $feature->description ?? '' }}">
                </div>
                <div class="col">
                    <select name="features[{{ $index }}][status]" class="form-control">
                        <option value="active" {{ ($feature->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($feature->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
                        </option>
                    </select>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFeature()">Add Feature</button>
</div>
