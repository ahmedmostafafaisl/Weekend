<div class="form-group">
    <label>Reservations</label>
    <div id="reservations-wrapper">
        @php $reservations = $unite->reservations ?? [null]; @endphp
        @foreach($reservations as $index => $res)
            <div class="row mb-2">
                <div class="col">
                    <input type="datetime-local" name="reservations[{{ $index }}][start]" class="form-control"
                        value="{{ $res->start ?? '' }}">
                </div>
                <div class="col">
                    <input type="datetime-local" name="reservations[{{ $index }}][end]" class="form-control"
                        value="{{ $res->end ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="reservations[{{ $index }}][morning_price]" class="form-control"
                        value="{{ $res->morning_price ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="reservations[{{ $index }}][evening_price]" class="form-control"
                        value="{{ $res->evening_price ?? '' }}">
                </div>
                <div class="col">
                    <input type="number" name="reservations[{{ $index }}][full_day_price]" class="form-control"
                        value="{{ $res->full_day_price ?? '' }}">
                </div>
                <div class="col">
                    <select name="reservations[{{ $index }}][status]" class="form-control">
                        <option value="active" {{ ($res->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($res->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="addReservation()">Add Reservation</button>
</div>
