@csrf

<div class="form-group">
    <label for="name">Name</label>
    <input type="text" name="name" value="{{ old('name', $adPackage->name ?? '') }}" class="form-control" required>
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" class="form-control">{{ old('description', $adPackage->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="type">Type</label>
    <select name="type" class="form-control" required>
        <option value="count" {{ old('type', $adPackage->type ?? '') == 'count' ? 'selected' : '' }}>Count</option>
        <option value="duration" {{ old('type', $adPackage->type ?? '') == 'duration' ? 'selected' : '' }}>Duration
        </option>
    </select>
</div>

<div class="form-group">
    <label for="count">Count</label>
    <input type="number" name="count" value="{{ old('count', $adPackage->count ?? '') }}" class="form-control">
</div>

<div class="form-group">
    <label for="duration">Duration</label>
    <input type="number" name="duration" value="{{ old('duration', $adPackage->duration ?? '') }}" class="form-control">
</div>

<div class="form-group">
    <label for="price">Price</label>
    <input type="number" step="0.01" name="price" value="{{ old('price', $adPackage->price ?? '') }}"
        class="form-control">
</div>

<div class="form-group">
    <label for="status">Status</label>
    <select name="status" class="form-control">
        <option value="active" {{ old('status', $adPackage->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $adPackage->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
        </option>
    </select>
</div>

<div class="form-group">
    <label for="image">Image</label>
    @if (isset($adPackage) && $adPackage->image)
        <img src="{{ asset( $adPackage->image) }}" width="100"><br>
    @endif
    <input type="file" name="image" class="form-control-file">
</div>

<button type="submit" class="btn btn-primary">Save</button>
