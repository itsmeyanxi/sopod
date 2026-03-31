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
                    <th class="px-4 py-3">Account Status</th>
                    <th class="px-4 py-3">Login Attempts</th>
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
                        @forelse($user->userRoles as $ur)
                            <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs mr-1">{{ $ur->subDepartment->name ?? '?' }}</span>
                        @empty
                            <span class="bg-gray-700 text-gray-500 px-2 py-1 rounded text-xs">No dept</span>
                        @endforelse
                    </td>
                    
                    {{-- Account Lock Status --}}
                    <td class="px-4 py-3">
                        @if($user->is_locked)
                            {{-- Manually locked by admin --}}
                            <div class="flex flex-col gap-1">
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold w-fit">
                                    🔒 LOCKED
                                </span>
                                @if($user->lockedBy)
                                    <span class="text-gray-500 text-xs">
                                        By: {{ $user->lockedBy->name }}<br>
                                        {{ $user->locked_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        @elseif(isset($user->login_attempts) && $user->login_attempts >= 6)
                            {{-- Auto-locked due to max attempts --}}
                            <div class="flex flex-col gap-1">
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold w-fit">
                                    🔒 LOCKED (Max Attempts)
                                </span>
                                <span class="text-gray-500 text-xs">
                                    Too many failed login attempts
                                </span>
                            </div>
                        @else
                            {{-- Active account --}}
                            <span class="bg-green-600 text-white px-2 py-1 rounded text-xs w-fit">
                                ✅ Active
                            </span>
                        @endif
                    </td>

                    {{-- Login Attempts Status --}}
                    <td class="px-4 py-3">
                        @if(isset($user->login_attempts) && $user->login_attempts >= 6)
                            <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold">
                                🔒 {{ $user->login_attempts }} attempts
                            </span>
                        @elseif(isset($user->login_attempts) && $user->login_attempts > 0)
                            <span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs">
                                ⚠️ {{ $user->login_attempts }} attempts
                            </span>
                        @else
                            <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">
                                0 attempts
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <input type="password" 
                                   value="{{ $user->password }}" 
                                   class="bg-gray-700 border border-gray-600 rounded px-3 py-1 text-white font-mono text-sm w-48" 
                                   id="password-{{ $user->id }}"
                                   readonly>
                            <button class="bg-gray-200 hover:bg-gray-300 text-white px-3 py-1 rounded toggle-password" 
                                    data-target="password-{{ $user->id }}"
                                    type="button"
                                    title="Show/Hide Hash">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-gray-500 text-xs">Hashed - cannot be decrypted</small>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            {{-- Lock/Unlock Button - Check BOTH manual lock AND max attempts --}}
                            @if($user->id !== auth()->id())
                                @php
                                    // Account is locked if EITHER manually locked OR max attempts reached
                                    $isLocked = $user->is_locked || (isset($user->login_attempts) && $user->login_attempts >= 6);
                                @endphp
                                
                                <form action="{{ route('admin.users.toggleLock', $user->id) }}" 
                                      method="POST" 
                                      class="inline lock-form">
                                    @csrf
                                    @if($isLocked)
                                        {{-- Show UNLOCK button when account is locked --}}
                                        <button type="submit" 
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                                                title="Unlock Account"
                                                data-user-name="{{ $user->name }}"
                                                data-action="unlock"
                                                data-attempts="{{ $user->login_attempts ?? 0 }}">
                                            🔓 Unlock
                                        </button>
                                    @else
                                        {{-- Show LOCK button when account is active --}}
                                        <button type="submit" 
                                                class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-sm"
                                                title="Lock Account"
                                                data-user-name="{{ $user->name }}"
                                                data-action="lock">
                                            🔒 Lock
                                        </button>
                                    @endif
                                </form>
                            @endif

                            {{-- Edit Button --}}
                            <a href="{{ route('admin.users.edit', $user->id) }}" 
                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm"
                               title="Edit User">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            
                            {{-- Reset Login Attempts Button - Show if attempts > 0 --}}
                            @if(isset($user->login_attempts) && $user->login_attempts > 0)
                            <form action="{{ route('users.reset-attempts', $user->id) }}" 
                                  method="POST" 
                                  class="inline reset-attempts-form">
                                @csrf
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                                        title="Reset Login Attempts"
                                        data-attempts="{{ $user->login_attempts }}">
                                    <i class="fas fa-sync"></i> Reset
                                </button>
                            </form>
                            @endif
                            
                            {{-- Delete Button --}}
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

    // Password toggle functionality
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

    // Lock/Unlock confirmation with SweetAlert2
    document.querySelectorAll('.lock-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button');
            const userName = button.getAttribute('data-user-name');
            const action = button.getAttribute('data-action');
            const attempts = button.getAttribute('data-attempts');
            
            if (action === 'lock') {
                Swal.fire({
                    title: 'Lock Account?',
                    html: `Lock account for <strong>${userName}</strong>?<br><br>They will not be able to log in until unlocked.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ea580c',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '🔒 Yes, lock it!',
                    cancelButtonText: 'Cancel',
                    background: '#ffffff',
                    color: '#1f2937'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                // Unlock action
                let message = `Unlock account for <strong>${userName}</strong>?<br><br>They will be able to log in again.`;
                
                // Add extra info if account was locked due to failed attempts
                if (attempts && attempts >= 6) {
                    message += `<br><br>⚠️ This account was locked due to <strong>${attempts} failed login attempts</strong>. Login attempts will be reset to 0.`;
                }
                
                Swal.fire({
                    title: 'Unlock Account?',
                    html: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '🔓 Yes, unlock it!',
                    cancelButtonText: 'Cancel',
                    background: '#ffffff',
                    color: '#1f2937'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });
    });

    // Reset Login Attempts confirmation
    document.querySelectorAll('.reset-attempts-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button');
            const attempts = button.getAttribute('data-attempts');
            
            Swal.fire({
                title: 'Reset Login Attempts?',
                html: `This will reset the <strong>${attempts} failed login attempts</strong> counter.<br><br>The user will be able to log in again.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, reset it!',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                color: '#1f2937'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Delete confirmation
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
                background: '#ffffff',
                color: '#1f2937'
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
            timer: 2500,
            background: '#ffffff',
            color: '#1f2937'
        });
    @endif

    // Error message
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
});
</script>
@endsection