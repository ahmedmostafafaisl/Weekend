<!-- Blade: resources/views/dashboard/web/backup_server_settings/_form.blade.php -->
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="user_id" class="form-label">{{ __('lang.user') }}</label>
    <select name="user_id" id="user_id" class="form-control" required>
        <option value="">{{ __('lang.select_users') }}</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" {{ old('user_id', $ad->user_id ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
</div>


<div class="mb-3">
    <label for="title" class="form-label">{{ __('lang.title') }}</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $ad->title ?? '') }}">
    @error('title') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">{{ __('lang.description') }}</label>
    <textarea name="description" class="form-control">{{ old('description', $ad->description ?? '') }}</textarea>
    @error('description') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="media" class="form-label">{{ __('lang.media') }} ({{ __('lang.you_can_select_multiple') }})</label>
    <input type="file" name="media[]" class="form-control" multiple>
    @error('media') <div class="text-danger">{{ $message }}</div> @enderror
    @if(isset($ad) && $ad->media)
        <div class="mt-2">
            <strong>{{ __('lang.existing_media') }}:</strong><br>
            @php
                // media can be a JSON string, a plain path string, or already an array
                $mediaItems = [];
                if (is_array($ad->media)) {
                    $mediaItems = $ad->media;
                } elseif (is_string($ad->media)) {
                    $decoded = json_decode($ad->media, true);
                    $mediaItems = is_array($decoded) ? $decoded : [$ad->media];
                }
            @endphp
            @foreach($mediaItems as $mediaItem)
                @php
                    // Each item may be a string path or an object/array with a 'media' key
                    $mediaPath = is_array($mediaItem) ? ($mediaItem['media'] ?? $mediaItem[0] ?? '') : (is_object($mediaItem) ? ($mediaItem->media ?? '') : $mediaItem);
                @endphp
                @if($mediaPath)
                    <img src="{{ asset('storage/' . ltrim($mediaPath, '/')) }}" width="100" class="me-2 mb-2 rounded"
                         onerror="this.style.opacity='.3'">
                @endif
            @endforeach
        </div>
    @endif
</div>

<button type="submit" class="btn btn-success">{{ $button }}</button>
<a href="{{ route('admin.ads.index') }}" class="btn btn-secondary"> {{ __('lang.cancel') }}</a>
