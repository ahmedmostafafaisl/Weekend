<div class="form-group">
    <label>Offers</label>
    <div id="offers-wrapper">
        @php $offers = $unite->offers ?? [null]; @endphp
        @foreach($offers as $index => $offer)
            <div class="row mb-2">
                <div class="col">
                    <input type="date" name="offers[{{ $index }}][start]" class="form-control"
                        value="{{ $offer->start ?? '' }}">
                </div>
                <div class="col">
                    <input type="date" name="offers[{{ $index }}][end]" class="form-control"
                        value="{{ $offer->end ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="offers[{{ $index }}][morning_price]" class="form-control"
                        placeholder="Morning Price" value="{{ $offer->morning_price ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="offers[{{ $index }}][evening_price]" class="form-control"
                        placeholder="Evening Price" value="{{ $offer->evening_price ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="offers[{{ $index }}][full_day_price]" class="form-control"
                        placeholder="Full Day Price" value="{{ $offer->full_day_price ?? '' }}">
                </div>
                <div class="col">
                    <select name="offers[{{ $index }}][status]" class="form-control">
                        <option value="active" {{ ($offer->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($offer->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
                        </option>
                    </select>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-success" onclick="addOffer()">Add Offer</button>
</div>
