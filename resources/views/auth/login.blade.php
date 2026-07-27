<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColdSpark AI — Login</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center">

    <!-- Background decoration -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600 rounded-full opacity-5 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-600 rounded-full opacity-5 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-500 rounded-full opacity-3 blur-3xl"></div>
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
            <p class="text-white text-sm mt-2">Personalized cold outreach at scale</p>
        </div>

        <!-- CARD -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-2xl">

            <h2 class="text-white font-bold text-xl mb-3">Sign in to your account to continue</h2>
            <!-- <p class="text-gray-500 text-sm mb-6">Sign in to your account to continue</p> -->

            <!-- Session Status -->
            @if (session('status'))
            <div class="bg-green-900 border border-green-700 rounded-xl px-4 py-3 text-green-300 text-sm mb-4">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block font-medium">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="you@company.com"
                        class="w-full bg-gray-800 border {{ $errors->has('email') ? 'border-red-500' : 'border-gray-700' }} rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                    @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-gray-400 text-sm font-medium">Password</label>
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-blue-400 text-xs hover:text-blue-300 transition-colors">
                            Forgot password?
                        </a>
                        @endif
                    </div>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full bg-gray-800 border {{ $errors->has('password') ? 'border-red-500' : 'border-gray-700' }} rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                    @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center mb-6">
                    <input type="checkbox" name="remember" id="remember"
                        class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-900">
                    <label for="remember" class="ml-2 text-gray-400 text-sm cursor-pointer">Remember me</label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40">
                    Sign In →
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-1 border-t border-gray-800"></div>
                <span class="px-3 text-gray-600 text-xs">New to ColdSpark?</span>
                <div class="flex-1 border-t border-gray-800"></div>
            </div>

            <!-- Register Link -->
            <a href="{{ route('register') }}"
                class="block w-full py-3 px-4 bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white font-semibold rounded-xl transition-all text-center text-sm">
                Create Free Account
            </a>
        </div>

        <!-- Features -->
        <div class="grid grid-cols-3 gap-3 mt-6">
            @foreach([['✉️','AI Email Generator'],['📊','Reply Tracking'],['👥','Team Sharing']] as $f)
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-3 text-center">
                <div class="text-xl mb-1">{{ $f[0] }}</div>
                <div class="text-gray-500 text-xs">{{ $f[1] }}</div>
            </div>
            @endforeach
        </div>

        <p class="text-center text-gray-600 text-xs mt-6">
            © 2026 ColdSpark AI · Powered by Groq AI
        </p>
    </div>

</body>
</html>