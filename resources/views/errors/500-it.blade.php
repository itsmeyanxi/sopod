<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Debug - IT Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .stack-trace {
            max-height: 500px;
            overflow-y: auto;
        }
        .error-highlight {
            background: #fee;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="max-w-7xl mx-auto">
        <!-- IT Badge -->
        <div class="mb-4 flex items-center gap-3">
            <span class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm">
                🔧 IT DEBUG MODE
            </span>
            <span class="text-gray-600 text-sm">
                Logged in as: <strong>{{ auth()->user()->name ?? 'IT User' }}</strong>
            </span>
        </div>

        <!-- Main Error Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
            <!-- Header -->
            <div class="bg-red-600 text-white p-4">
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ class_basename($exception) }}</span>
                </h1>
            </div>

            <!-- Error Message -->
            <div class="p-6 border-b border-gray-200 error-highlight">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Error Message:</h2>
                <p class="text-red-700 text-lg font-mono">{{ $exception->getMessage() }}</p>
            </div>

            <!-- File Location -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Location:</h2>
                <p class="text-gray-700 font-mono text-sm">
                    <strong>File:</strong> {{ $exception->getFile() }}<br>
                    <strong>Line:</strong> {{ $exception->getLine() }}
                </p>
            </div>

            <!-- Stack Trace -->
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Stack Trace:</h2>
                <div class="stack-trace">
                    <pre>{{ $exception->getTraceAsString() }}</pre>
                </div>
            </div>
        </div>

        <!-- Request Info -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
            <div class="bg-blue-600 text-white p-3">
                <h2 class="text-lg font-bold">Request Information</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">URL:</h3>
                    <p class="text-sm font-mono bg-gray-100 p-2 rounded">{{ request()->fullUrl() }}</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Method:</h3>
                    <p class="text-sm font-mono bg-gray-100 p-2 rounded">{{ request()->method() }}</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">IP Address:</h3>
                    <p class="text-sm font-mono bg-gray-100 p-2 rounded">{{ request()->ip() }}</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">User Agent:</h3>
                    <p class="text-sm font-mono bg-gray-100 p-2 rounded truncate">{{ request()->userAgent() }}</p>
                </div>
            </div>
        </div>

        <!-- Request Data -->
        @if(request()->all())
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
            <div class="bg-green-600 text-white p-3">
                <h2 class="text-lg font-bold">Request Data</h2>
            </div>
            <div class="p-6">
                <pre>{{ json_encode(request()->except(['password', 'password_confirmation']), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-4 mb-8">
            <button onclick="window.location.reload()" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                🔄 Refresh Page
            </button>
            <a href="{{ route('dashboard') }}" 
               class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
                🏠 Go to Dashboard
            </a>
            <button onclick="copyError()" 
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                📋 Copy Error Details
            </button>
        </div>

        <!-- Footer Note -->
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> This detailed error page is only visible to users with the IT role. 
                Other users see a user-friendly error page instead.
            </p>
        </div>
    </div>

    <script>
        function copyError() {
            const errorDetails = `
Error: {{ $exception->getMessage() }}
File: {{ $exception->getFile() }}
Line: {{ $exception->getLine() }}
URL: {{ request()->fullUrl() }}

Stack Trace:
{{ $exception->getTraceAsString() }}
            `.trim();

            navigator.clipboard.writeText(errorDetails).then(() => {
                alert('Error details copied to clipboard!');
            });
        }
    </script>
</body>
</html>