@extends('dashboard.master')

@section('title', __('lang.company_name'))


@section('content')
        <div class="container">
            <h2>Create Offer</h2>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>There were some errors:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('unite_offers.store') }}" method="POST">
                @csrf

                {{-- Type Dropdown --}}
                <div class="mb-3">
                    <label for="type" class="form-label">Unit Type</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="">Select Type</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Unite Dropdown --}}
                <div class="mb-3">
                    <label for="unite_id" class="form-label">Unit</label>
                    <select name="unite_id" id="unite_id" class="form-control" required>
                        <option value="">Select Unit</option>
                        {{-- Populated via JS --}}
                    </select>
                </div>

                {{-- Offer Fields --}}
        {{-- Start Date --}}
        <div class="mb-3">
            <label for="start" class="form-label">Start Date</label>
            <input type="date" name="start" id="start" class="form-control" required>
        </div>

        {{-- End Date --}}
        <div class="mb-3">
            <label for="end" class="form-label">End Date</label>
            <input type="date" name="end" id="end" class="form-control" required>
        </div>
                <div class="mb-3">
                    <label for="morning_price" class="form-label">Morning Price</label>
                    <input type="number" step="0.01" name="morning_price" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="evening_price" class="form-label">Evening Price</label>
                    <input type="number" step="0.01" name="evening_price" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="full_day_price" class="form-label">Full Day Price</label>
                    <input type="number" step="0.01" name="full_day_price" class="form-control">
                </div>
    {{-- Status --}}
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
@endsection

@section('scripts')
    <script>
        const allUnites = @json($unites);

        document.getElementById('type').addEventListener('change', function () {
            const type = this.value;
            const uniteDropdown = document.getElementById('unite_id');
            uniteDropdown.innerHTML = '<option value="">Select Unit</option>';

            if (type && allUnites[type]) {
                allUnites[type].forEach(function (unite) {
                    const option = document.createElement('option');
                    option.value = unite.id;
                    option.text = unite.name;
                    uniteDropdown.appendChild(option);
                });
            }
        });
    </script>
@endsection

