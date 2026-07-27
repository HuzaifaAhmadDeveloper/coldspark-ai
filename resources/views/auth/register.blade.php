<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColdSpark AI — Register</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600 rounded-full opacity-5 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-600 rounded-full opacity-5 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md px-6 py-8 relative z-10">

        <!-- LOGO -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4 shadow-lg shadow-blue-500/25">
                <span class="text-3xl">✉</span>
            </div>
            <h1 class="text-3xl font-bold text-white">
                Cold<span class="text-blue-400">Spark</span> <span class="text-purple-400">AI</span>
            </h1>
            <p class="text-white text-sm mt-2">Start writing better cold emails today</p>
        </div>

        <!-- CARD -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-2xl">

            <h2 class="text-white font-bold text-xl mb-1">Create your account 🚀</h2>
            <p class="text-gray-500 text-sm mb-6">Get 10 free credits — no credit card required</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block font-medium">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        placeholder="Huzaifa Ahmad"
                        class="w-full bg-gray-800 border {{ $errors->has('name') ? 'border-red-500' : 'border-gray-700' }} rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-all">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block font-medium">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        placeholder="you@company.com"
                        class="w-full bg-gray-800 border {{ $errors->has('email') ? 'border-red-500' : 'border-gray-700' }} rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-all">
                    @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block font-medium">Password</label>
                    <input type="password" name="password" required
                        placeholder="Min 8 characters"
                        class="w-full bg-gray-800 border {{ $errors->has('password') ? 'border-red-500' : 'border-gray-700' }} rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-all">
                    @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label class="text-gray-400 text-sm mb-2 block font-medium">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        placeholder="••••••••"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-all">
                </div>

                <!-- Free plan badge -->
                <div class="bg-blue-900 border border-blue-700 rounded-xl px-4 py-3 mb-4 flex items-center gap-3">
                    <span class="text-2xl">🎁</span>
                    <div>
                        <div class="text-blue-300 text-sm font-bold">Free Plan — 10 Credits</div>
                        <div class="text-blue-400 text-xs">Generate 10 email sequences free</div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/25">
                    Create Account →
                </button>
            </form>

            <div class="flex items-center my-6">
                <div class="flex-1 border-t border-gray-800"></div>
                <span class="px-3 text-gray-600 text-xs">Already have an account?</span>
                <div class="flex-1 border-t border-gray-800"></div>
            </div>

            <a href="{{ route('login') }}"
                class="block w-full py-3 px-4 bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white font-semibold rounded-xl transition-all text-center text-sm">
                Sign In
            </a>
        </div>

        <p class="text-center text-gray-600 text-xs mt-6">
            © 2026 ColdSpark AI · Powered by Groq AI
        </p>
    </div>

</body>
</html>