<div class="form-group">
    <label>Images</label>
    <input type="file" name="images[]" class="form-control" multiple>

    @if(!empty($unite) && $unite->images->count())
        <div class="row mt-2">
            @foreach($unite->images as $image)
                <div class="col-md-3">
                    <img src="{{ asset('storage/' . $image->image_path) }}" class="img-thumbnail" width="100%">
                </div>
            @endforeach
        </div>
    @endif
</div>
