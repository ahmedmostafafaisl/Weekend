


    <div class="container">
        <h2>{{ isset($subscription) ? 'Edit Subscription' : 'Create Subscription' }}</h2>

        <form
            action="{{ isset($subscription) ? route('subscriptions.update', $subscription->id) : route('subscriptions.store') }}"
            method="POST">
            @csrf
            @if(isset($subscription))
                @method('PUT')
            @endif

            {{-- User ID --}}
            <div class="form-group">
                <label for="user_id">User</label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $subscription->user_id ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div class="form-group">
                <label for="type">Subscription Type</label>
                <select name="type" id="type" class="form-control" required onchange="togglePackages(this.value)">
                    <option value="">Select Type</option>
                    <option value="ad" {{ old('type', $subscription->type ?? '') == 'ad' ? 'selected' : '' }}>Ad</option>
                    <option value="property" {{ old('type', $subscription->type ?? '') == 'property' ? 'selected' : '' }}>
                        Property</option>
                    <option value="percentage" {{ old('type', $subscription->type ?? '') == 'percentage' ? 'selected' : '' }}>
                        Percentage</option>
                </select>
            </div>

            {{-- Ad Packages --}}
            <div class="form-group" id="adPackageDiv" style="display: none;">
                <label for="ad_package">Ad Package</label>
                <select name="package_id" id="ad_package" class="form-control">
                    <option value="">Select Ad Package</option>
                    @foreach($adPackages as $package)
                        <option value="{{ $package->id }}" {{ old('package_id', $subscription->package_id ?? '') == $package->id && ($subscription->type ?? '') == 'ad' ? 'selected' : '' }}>
                            {{ $package->name }} - {{ $package->price }} EGP
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Property Packages --}}
            <div class="form-group" id="propertyPackageDiv" style="display: none;">
                <label for="property_package">Property Package</label>
                <select name="package_id" id="property_package" class="form-control">
                    <option value="">Select Property Package</option>
                    @foreach($propertyPackages as $package)
                        <option value="{{ $package->id }}" {{ old('package_id', $subscription->package_id ?? '') == $package->id && ($subscription->type ?? '') == 'property' ? 'selected' : '' }}>
                            {{ $package->name }} - {{ $package->price }} EGP
                        </option>
                    @endforeach
                </select>
            </div>
<div class="form-group">
    <label for="status">Status</label>
    <select name="status" class="form-control" required>
        <option value="active" {{ old('status', $subscription->status ?? '') == 'active' ? 'selected' : '' }}>Active
        </option>
        <option value="inactive" {{ old('status', $subscription->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
        </option>
    </select>
</div>
            {{-- Submit --}}
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ isset($subscription) ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function togglePackages(type) {
            const adSelect = document.getElementById('ad_package');
            const propertySelect = document.getElementById('property_package');

            document.getElementById('adPackageDiv').style.display = (type === 'ad') ? 'block' : 'none';
            document.getElementById('propertyPackageDiv').style.display = (type === 'property') ? 'block' : 'none';

            adSelect.disabled = type !== 'ad';
            propertySelect.disabled = type !== 'property';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedType = document.getElementById('type').value;
            togglePackages(selectedType);
        });
    </script>
