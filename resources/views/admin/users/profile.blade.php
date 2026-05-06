@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto mt-8 space-y-6">

    @if ($errors->any())
        <div class="bg-red-600 text-white p-3 rounded">
            <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- FORM 1: Profile Info --}}
    <div class="bg-gray-800 text-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Profile Info</h2>
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Departments</label>
                <div class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-gray-300">
                    @php $userRoles = auth()->user()->userRoles()->with('subDepartment.department')->get(); @endphp
                    @forelse($userRoles as $ur)
                        <span class="inline-block bg-blue-600 text-white px-2 py-1 rounded text-xs mr-1 mb-1">{{ $ur->subDepartment->department->name }} - {{ $ur->subDepartment->name }}</span>
                    @empty
                        <span class="text-gray-300 text-sm">No departments assigned</span>
                    @endforelse
                </div>
                <small class="text-gray-400 text-xs">Managed by admin via RBAC</small>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Save Info</button>
            </div>
        </form>
    </div>

    {{-- FORM 2: Change Password --}}
    <div class="bg-gray-800 text-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Change Password</h2>
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm mb-1">New Password <span class="text-gray-400 text-xs">(min. 6 characters)</span></label>
                <div class="relative">
                    <input type="password" name="password" id="password"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 pr-12 text-white" placeholder="New password">
                    <button type="button" onclick="toggleVis('password', this)"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-gray-600 text-white px-3 py-1 rounded"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 pr-12 text-white" placeholder="Confirm password">
                    <button type="button" onclick="toggleVis('password_confirmation', this)"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-gray-600 text-white px-3 py-1 rounded"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">Change Password</button>
            </div>
        </form>
    </div>

    {{-- FORM 3: E-Signature --}}
    <div class="bg-gray-800 text-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">E-Signature</h2>
        @if(auth()->user()->esignature)
            <div class="mb-4 p-3 bg-gray-700 rounded text-center">
                <p class="text-sm text-gray-300 mb-2">Current Signature:</p>
                <img src="{{ asset('storage/' . auth()->user()->esignature) }}" alt="Signature" class="max-h-20 mx-auto">
            </div>
        @endif
        <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm mb-1">Upload Signature <span class="text-gray-400 text-xs">(PNG/JPG, max 2MB)</span></label>
                <input type="file" name="esignature" accept="image/png,image/jpg,image/jpeg"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg p-2 text-white" required>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                    {{ auth()->user()->esignature ? 'Replace Signature' : 'Upload Signature' }}
                </button>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleVis(id, btn) {
    const f = document.getElementById(id);
    const i = btn.querySelector('i');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.classList.toggle('fa-eye');
    i.classList.toggle('fa-eye-slash');
}
@if(session('success'))
    Swal.fire({ icon: 'success', title: 'Success!', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000, background: '#fff', color: '#1f2937' });
@endif
@if(session('error'))
    Swal.fire({ icon: 'error', title: 'Error!', text: '{{ session('error') }}', background: '#fff', color: '#1f2937' });
@endif
</script>
@endsection
