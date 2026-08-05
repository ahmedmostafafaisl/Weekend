
@extends('dashboard.master')

@section('title', __('lang.company_name'))
@section('content')
    <div class="container">
        <h3>{{ isset($user) ? 'Edit User' : 'Add User' }}</h3>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            <div class="form-group">
                <label>Name</label>
                <input name="name" value="{{ old('name', $user->name ?? '') }}"
                    class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Email</label>
                <input name="email" value="{{ old('email', $user->email ?? '') }}"
                    class="form-control @error('email') is-invalid @enderror" type="email" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                    class="form-control @error('phone') is-invalid @enderror">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror">
                    <option value="active" {{ old('status', $user->status ?? '') === 'active' ? 'selected' : '' }}>Active
                    </option>
                    <option value="inactive" {{ old('status', $user->status ?? '') === 'inactive' ? 'selected' : '' }}>
                        Inactive</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Password @if(isset($user)) (leave blank if unchanged) @endif</label>
                <div class="input-group">
                    <input name="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        id="password-input">
                    <div class="input-group-append">
                        <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                            <i class="fe fe-eye" id="toggle-password-icon"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-group">
                    <input name="password_confirmation" type="password" class="form-control" id="password-confirmation">
                    <div class="input-group-append">
                        <span class="input-group-text" onclick="toggleConfirmPassword()" style="cursor: pointer;">
                            <i class="fe fe-eye" id="toggle-confirm-password-icon"></i>
                        </span>
                    </div>
                </div>
            </div>


            <button class="btn btn-success mt-3">Save</button>
        </form>
    </div>

    @push('scripts')
        <script>
            function togglePassword() {
                const input = document.getElementById('password-input');
                const icon = document.getElementById('toggle-password-icon');

                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fe-eye");
                    icon.classList.add("fe-eye-off");
                } else {
                    input.type = "password";
                    icon.classList.remove("fe-eye-off");
                    icon.classList.add("fe-eye");
                }
            }

            function toggleConfirmPassword() {
                    const input = document.getElementById('password-confirmation');
                    const icon = document.getElementById('toggle-confirm-password-icon');

                    if (input.type === "password") {
                        input.type = "text";
                        icon.classList.remove("fe-eye");
                        icon.classList.add("fe-eye-off");
                    } else {
                        input.type = "password";
                        icon.classList.remove("fe-eye-off");
                        icon.classList.add("fe-eye");
                    }
                }
        </script>
    @endpush
@endsection
