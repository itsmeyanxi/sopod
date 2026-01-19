<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong - SOPOD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Main Error Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Decorative Header -->
            <div class="h-2 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
            
            <div class="p-10 lg:p-12 text-center">
                <!-- Error Image -->
                <div class="mb-8">
                    <img src="{{ asset('images/maintenance.jpg') }}" 
                         alt="System Maintenance" 
                         class="mx-auto max-w-sm w-full rounded-xl shadow-lg"
                         onerror="this.style.display='none'">
                </div>

                <!-- Error Message -->
                <h1 class="text-4xl font-bold text-gray-800 mb-4">
                    We'll Be Right Back!
                </h1>
                
                <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                    We're experiencing a temporary technical issue. Our team has been automatically notified and is working to resolve this as quickly as possible.
                </p>

                <!-- Info Box -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6 mb-8">
                    <div class="flex items-start gap-4">
                        <div class="text-3xl">💡</div>
                        <div class="text-left">
                            <p class="text-sm font-bold text-blue-900 mb-2">What you can do:</p>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Refresh this page to try again</li>
                                <li>• Return to the dashboard</li>
                                <li>• Contact IT support if the issue persists</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="window.location.reload()" 
                            class="flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition shadow-lg font-semibold text-lg">
                        <span>🔄</span> Refresh Page
                    </button>
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center justify-center gap-2 px-8 py-4 bg-white border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition shadow-md font-semibold text-lg">
                        <span>🏠</span> Go to Dashboard
                    </a>
                </div>

                <!-- Support Section -->
                <div class="mt-10 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-500 mb-2">Still having trouble?</p>
                    <a href="http://mtcresolveit.meatplus.ph/public/ticket/index.php?entity=1" 
                       target="_blank" 
                       class="inline-block px-6 py-3 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-gray-700 font-medium">
                        📧 Contact IT Support
                    </a>
                    <p class="text-sm text-gray-500 mt-4">
                        Error ID: {{ $errorId ?? \Illuminate\Support\Str::random(8) }} • {{ $timestamp ?? now()->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Additional Help Card -->
        <div class="mt-6 bg-white/80 backdrop-blur rounded-xl p-6 text-center shadow-md">
            <p class="text-sm text-gray-600">
                This is a temporary issue. Most problems resolve within a few minutes.
            </p>
        </div>
        <div class="mt-6 bg-yellow-100 border border-yellow-400 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-700">
                <strong>DEBUG:</strong> You're seeing this friendly page because your role is: 
                <span class="font-bold text-red-600">{{ $userRole ?? 'Unknown' }}</span>
            </p>
        </div>
    </div>
</body>
</html>