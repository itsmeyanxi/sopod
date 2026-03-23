@extends('layouts.app')

@section('title', 'Add User')

@section('content')
<div class="max-w-3xl mx-auto bg-white text-gray-800 p-8 rounded-lg mt-8 shadow-md">
    <h2 class="text-2xl font-bold mb-6">Add New User</h2>

    @if ($errors->any())
        <div class="bg-red-600 text-white p-3 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2 text-gray-800 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2 text-gray-800 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password"
                   class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2 text-gray-800 focus:ring-blue-500" required>
        </div>

        <div class="bg-yellow-900/30 border border-yellow-600/30 rounded-lg p-3 mb-4">
            <p class="text-yellow-300 text-sm"><i class="fas fa-info-circle mr-1"></i> After creating the user, assign them to a sub-department via the <a href="{{ route('rbac.index') }}" class="underline">RBAC Management</a> page to grant module access.</p>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('dashboard') }}" class="bg-gray-600 hover:bg-gray-100 text-gray-800 px-4 py-2 rounded-lg">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Save</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 2000,
        background: '#ffffff',
        color: '#1f2937'
    });
@endif

@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        showConfirmButton: true,
        background: '#ffffff',
        color: '#1f2937'
    });
@endif
</script>
@endsection
