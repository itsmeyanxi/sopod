@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto bg-gray-800 text-white p-8 rounded-lg mt-8 shadow-md">
    <h2 class="text-2xl font-bold mb-6">My Profile</h2>

    @if ($errors->any())
        <div class="bg-red-600 text-white p-3 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" 
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Role</label>
            <input type="text" value="{{ auth()->user()->role }}" 
                   class="w-full bg-gray-600 border border-gray-600 rounded-lg p-2 text-gray-400 cursor-not-allowed" 
                   disabled>
            <small class="text-gray-400 text-xs">Role cannot be changed</small>
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

        <div class="mb-4">
            <label class="block text-sm mb-1">Confirm New Password</label>
            <div class="relative">
                <input type="password" 
                       name="password_confirmation" 
                       id="password_confirmation"
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 pr-12 text-white focus:ring-blue-500"
                       placeholder="Confirm new password">
                <button type="button" 
                        id="togglePasswordConfirm"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Update Profile</button>
        </div>
    </form>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordConfirmField = document.getElementById('password_confirmation');
    
    togglePassword.addEventListener('click', function() {
        const icon = this.querySelector('i');
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

    togglePasswordConfirm.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (passwordConfirmField.type === 'password') {
            passwordConfirmField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordConfirmField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Show success message
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 2000,
        background: '#1f2937',
        color: '#fff'
    });
@endif

// Show error message
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