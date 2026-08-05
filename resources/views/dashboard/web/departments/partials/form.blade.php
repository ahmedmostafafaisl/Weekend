{{-- Show validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger rounded p-3">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isEdit = isset($department);
@endphp

<form
    action="{{ $isEdit ? route('departments.update', $department->id) : route('departments.store') }}"
    method="POST"
    enctype="multipart/form-data"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Name --}}
    <div class="form-group mb-3">
        <label for="name">Department Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required>
        @error('name')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="form-group mb-3">
        <label for="description">Description</label>
        <textarea name="description" class="form-control">{{ old('description', $department->description ?? '') }}</textarea>
        @error('description')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Type --}}
    <div class="form-group mb-3">
        <label for="type">Type</label>
        <select name="type" class="form-control">
            @foreach(['stadium', 'hall', 'lounge', 'camp'] as $type)
                <option value="{{ $type }}" {{ old('type', $department->type ?? '') === $type ? 'selected' : '' }}>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
        @error('type')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Location --}}
    <div class="form-group mb-3">
        <label for="location">Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $department->location ?? '') }}">
        @error('location')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Latitude --}}
    <div class="form-group mb-3">
        <label for="latitude">Latitude</label>
        <input type="text" name="latitude" class="form-control" value="{{ old('latitude', $department->latitude ?? '') }}">
        @error('latitude')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Longitude --}}
    <div class="form-group mb-3">
        <label for="longitude">Longitude</label>
        <input type="text" name="longitude" class="form-control" value="{{ old('longitude', $department->longitude ?? '') }}">
        @error('longitude')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Social Media --}}
    <div class="form-group mb-3">
        <label for="social_media">Social Media</label>
        <div class="d-flex flex-wrap gap-2">
            <input type="text" name="facebook" class="form-control" placeholder="Facebook" value="{{ old('facebook', $department->facebook ?? '') }}">
            <input type="text" name="twitter" class="form-control" placeholder="Twitter" value="{{ old('twitter', $department->twitter ?? '') }}">
            <input type="text" name="instagram" class="form-control" placeholder="Instagram" value="{{ old('instagram', $department->instagram ?? '') }}">
            <input type="text" name="youtube" class="form-control" placeholder="YouTube" value="{{ old('youtube', $department->youtube ?? '') }}">
            <input type="text" name="website" class="form-control" placeholder="Website" value="{{ old('website', $department->website ?? '') }}">
            <input type="text" name="whatsapp" class="form-control" placeholder="WhatsApp" value="{{ old('whatsapp', $department->whatsapp ?? '') }}">
            <input type="text" name="snapchat" class="form-control" placeholder="Snapchat" value="{{ old('snapchat', $department->snapchat ?? '') }}">
            <input type="text" name="tiktok" class="form-control" placeholder="TikTok" value="{{ old('tiktok', $department->tiktok ?? '') }}">
        </div>
        @error('social_media')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- Status --}}
    <div class="form-group mb-3">
        <label for="status">Status</label>
        <select name="status" class="form-control">
            <option value="active" {{ old('status', $department->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $department->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    {{-- User ID --}}
    @if (!request()->expectsJson())
        <div class="form-group mb-3">
            <label for="user_id">User</label>
            <select name="user_id" class="form-control" required>
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $department->user_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('user_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    @endif

    {{-- Images --}}
    <div class="form-group mb-3">
        <label for="images">Images</label>
        <input type="file" name="images[]" class="form-control" multiple>
        @error('images')
            <div class="text-danger">{{ $message }}</div>
        @enderror
        @if ($errors->has('images.*'))
            @foreach ($errors->get('images.*') as $messages)
                @foreach ($messages as $message)
                    <div class="text-danger">{{ $message }}</div>
                @endforeach
            @endforeach
        @endif

        {{-- Preview existing images --}}
        @if ($isEdit && isset($department->images) && count($department->images))
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($department->images as $image)
                    <div class="border rounded p-1">
                        <img src="{{ asset('storage/' . $image->image_path) }}" width="100" alt="Image">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Submit --}}
    <div class="form-group mt-4">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Update' : 'Create' }} Department
        </button>
    </div>
</form>
