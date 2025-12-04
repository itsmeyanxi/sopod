@extends('layouts.guest')
@section('title', 'Login')

@section('content')
@php
    // Convert logo to base64 (same method as PDF)
    $logoPath = public_path('images/sopod-logo.png');
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoBase64 = 'data:image/png;base64,' . $logoData;
    }
@endphp

<div class="flex justify-center items-center h-screen bg-gray-900">
    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-96">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Company Logo" class="logo-image">
            @else
                <div class="text-white text-sm">Logo not found</div>
            @endif
        </div>

        <h2 class="text-white text-2xl font-bold mb-6 text-center">Login</h2>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <!-- Email Field -->
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    class="w-full p-2 rounded bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 outline-none" 
                    placeholder="Enter your email" 
                    required>
            </div>

            <!-- Password Field -->
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="w-full p-2 rounded bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 outline-none" 
                    placeholder="Enter your password" 
                    required>
            </div>

            <!-- Error Message -->
            @if ($errors->any())
                <div class="text-red-500 mb-4 text-sm text-center bg-red-900/40 p-2 rounded">
                    @if($errors->has('TokenMismatchException') || session('message') == 'CSRF token mismatch.')
                        Your session has expired. Please refresh the page and try again.
                    @else
                        {{ $errors->first() }}
                    @endif
                </div>
            @endif

            <!-- Success Message -->
            @if (session('success'))
                <div class="text-green-500 mb-4 text-sm text-center bg-green-900/40 p-2 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Submit Button -->
            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 transition duration-300 text-white p-2 rounded font-semibold">
                Login
            </button>

            <!-- Registration Disabled -->
            <p class="mt-4 text-center text-gray-400">
                Need an account? Contact your administrator.
                <a href="http://mtcresolveit.meatplus.ph/public/ticket/index.php?entity=1"
                    target="_blank"
                    class="text-blue-400 hover:underline font-semibold">
                    Send a request
                </a>
            </p>
        </form>
    </div>
</div>

<style>
    .logo-image {
        width: 120px;
        height: 120px;
        object-fit: contain;
    }
</style>

<script>
// Auto-refresh CSRF token every 30 minutes to prevent expiration
setInterval(function() {
    fetch('{{ route("login") }}', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => response.text())
    .then(html => {
        // Extract new CSRF token from response
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newToken = doc.querySelector('input[name="_token"]');
        if (newToken) {
            document.querySelector('input[name="_token"]').value = newToken.value;
        }
    }).catch(err => console.log('Token refresh failed'));
}, 30 * 60 * 1000); // 30 minutes

// 🔥 Clear session storage when user closes tab
window.addEventListener('beforeunload', function() {
    // Send logout request when tab/browser closes
    navigator.sendBeacon('{{ route("logout") }}', new FormData());
});
</script>
@endsection