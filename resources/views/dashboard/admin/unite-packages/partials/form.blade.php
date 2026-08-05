@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('lang.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $package->name ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.price') }}</label>
        <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $package->price ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.men_capacity') }}</label>
        <input type="number" name="men_capacity" class="form-control" value="{{ old('men_capacity', $package->men_capacity ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('lang.women_capacity') }}</label>
        <input type="number" name="women_capacity" class="form-control" value="{{ old('women_capacity', $package->women_capacity ?? '') }}">
    </div>
</div>
