@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="flex justify-center items-center h-screen bg-gray-900">
    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-96">
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
                    {{ $errors->first() }}
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
                <a href="http://localhost:8080/dolibarr/public/ticket/index.php?entity=1"
                    target="_blank"
                    class="text-blue-400 hover:underline font-semibold">
                    Send a request
                </a>
            </p>
        </form>
    </div>
</div>
@endsection
