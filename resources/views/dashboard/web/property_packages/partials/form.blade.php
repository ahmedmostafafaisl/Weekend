<form action="{{ $route }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $propertyPackage->name ?? '') }}">
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description"
            class="form-control">{{ old('description', $propertyPackage->description ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label>Type</label>
        <select name="type" class="form-control">
            <option value="time" {{ old('type', $propertyPackage->type ?? '') === 'time' ? 'selected' : '' }}>Time
            </option>
            <option value="percentage" {{ old('type', $propertyPackage->type ?? '') === 'percentage' ? 'selected' : '' }}>
                Percentage</option>
        </select>
    </div>

    <div class="form-group">
        <label>Duration (if type = time)</label>
        <input type="number" name="duration" class="form-control"
            value="{{ old('duration', $propertyPackage->duration ?? '') }}">
    </div>

    <div class="form-group">
        <label>Percentage (if type = percentage)</label>
        <input type="number" name="percentage" class="form-control"
            value="{{ old('percentage', $propertyPackage->percentage ?? '') }}">
    </div>

    <div class="form-group">
        <label>Price</label>
        <input type="number" step="0.01" name="price" class="form-control"
            value="{{ old('price', $propertyPackage->price ?? '') }}">
    </div>

    <div class="form-group">
        <label>Image</label>
        <input type="file" name="image" class="form-control">
        @if (!empty($propertyPackage->image))
            <img src="{{ asset( $propertyPackage->image) }}" alt="Image" height="60">
        @endif
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="active" {{ old('status', $propertyPackage->status ?? '') === 'active' ? 'selected' : '' }}>
                Active</option>
            <option value="inactive" {{ old('status', $propertyPackage->status ?? '') === 'inactive' ? 'selected' : '' }}>
                Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
</form>
