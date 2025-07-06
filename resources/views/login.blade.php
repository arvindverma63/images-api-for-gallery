<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-r from-blue-100 to-blue-300 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 space-y-6">
        <h2 class="text-2xl font-bold text-center text-gray-700">Enter Passcode</h2>

        @if(session('error'))
            <div class="bg-red-100 text-red-600 p-3 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-gray-600">Passcode</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    class="w-full px-4 py-2 mt-1 border rounded-md focus:ring-2 focus:ring-blue-400 focus:outline-none"
                    placeholder="Enter secret passcode"
                />
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition"
                >
                    Submit
                </button>
            </div>
        </form>
    </div>

</body>
</html>
