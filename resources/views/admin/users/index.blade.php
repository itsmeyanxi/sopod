@extends('layouts.app')

@section('title', 'User List')

@section('content')
<div class="max-w-7xl mx-auto bg-gray-800 text-white p-8 rounded-lg mt-8 shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">User Management</h2>
        <a href="{{ route('admin.users.create') }}" class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-4 py-2 rounded transition w-full sm:w-auto text-center">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>

    {{-- Search Bar --}}
    <div class="mb-4">
        <input 
            id="searchInput" 
            type="text" 
            placeholder="Search name / email / role" 
            class="border border-gray-700 bg-gray-800 text-gray-200 rounded px-3 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-purple-500"
        />
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left" id="usersTable">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Password Hash</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                @foreach($users as $user)
                <tr class="border-b border-gray-700 hover:bg-gray-700 user-row">
                    <td class="px-4 py-3">{{ $user->id }}</td>
                    <td class="px-4 py-3">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-blue-600 text-white px-2 py-1 rounded text-sm">{{ $user->role }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <input type="password" 
                                   value="{{ $user->password }}" 
                                   class="bg-gray-700 border border-gray-600 rounded px-3 py-1 text-white font-mono text-sm w-48" 
                                   id="password-{{ $user->id }}"
                                   readonly>
                            <button class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded toggle-password" 
                                    data-target="password-{{ $user->id }}"
                                    type="button"
                                    title="Show/Hide Hash">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-gray-400 text-xs">Hashed - cannot be decrypted</small>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.users.edit', $user->id) }}" 
                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm"
                               title="Edit User">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" 
                                  method="POST" 
                                  class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
                                        title="Delete User">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const userRows = document.querySelectorAll('.user-row');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();

        userRows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const role = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Password toggle functionality (shows/hides the hash)
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordField = document.getElementById(targetId);
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
    });

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                background: '#1f2937',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Success message
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

    // Error message
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
});
</script>
@endsection