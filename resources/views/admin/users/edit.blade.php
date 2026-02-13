@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto bg-gray-800 text-white p-8 rounded-lg mt-8 shadow-md">
    <h2 class="text-2xl font-bold mb-6">Edit User</h2>

    @if ($errors->any())
        <div class="bg-red-600 text-white p-3 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        {{-- 🆕 MULTI-ROLE SELECT --}}
        <div class="mb-4">
            <label class="block text-sm mb-1">Roles <span class="text-red-500">*</span></label>
            <select name="roles[]" id="roles" class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white" multiple required>
                @foreach($roles as $role)
                    <option value="{{ $role }}" 
                        {{ (old('roles') && in_array($role, old('roles'))) || (isset($user) && in_array($role, $user->roles ?? [])) ? 'selected' : '' }}>
                        {{ $role }}
                    </option>
                @endforeach
            </select>
            <small class="text-gray-400 text-xs mt-1 block">Current roles: 
                @if(!empty($user->roles))
                    @foreach($user->roles as $role)
                        <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs">{{ $role }}</span>
                    @endforeach
                @else
                    <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">{{ $user->role }}</span>
                @endif
            </small>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">
                New Password 
                <span class="text-gray-400 text-xs">(Leave blank to keep current password)</span>
            </label>
            <div class="relative">
                <input type="password" 
                       name="password" 
                       id="password"
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 pr-12 text-white focus:ring-blue-500"
                       placeholder="Enter new password (min. 6 characters)">
                <button type="button" 
                        id="togglePassword"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <small class="text-gray-400 text-xs">Minimum 6 characters</small>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Update User</button>
        </div>
    </form>
</div>

{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

{{-- Select2 Dark Theme CSS --}}
<style>
.select2-container--default .select2-selection--multiple {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: white !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #3b82f6 !important;
    border: none !important;
    color: white !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white !important;
    margin-right: 5px !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fee2e2 !important;
}
.select2-dropdown {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
}
.select2-container--default .select2-results__option {
    color: white !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6 !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #1f2937 !important;
    border-color: #4b5563 !important;
    color: white !important;
}
</style>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- jQuery (required for Select2) --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{{-- Select2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('#roles').select2({
        placeholder: 'Select roles',
        allowClear: false,
        closeOnSelect: false
    });

    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const icon = togglePassword.querySelector('i');
    
    togglePassword.addEventListener('click', function() {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Show error message if validation fails
@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        showConfirmButton: true,
        background: '#1f2937',
        color: '#fff'
    });
@endif
</script>
@endsection