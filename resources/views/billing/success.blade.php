<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColdSpark AI — Payment Successful</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
        <div class="text-6xl mb-6">🎉</div>
        <h1 class="text-3xl font-bold mb-4">Payment Successful!</h1>
        <p class="text-gray-400 mb-2">You are now on the <span class="text-blue-400 font-bold">{{ $plan }}</span> plan.</p>
        <p class="text-gray-400 mb-8"><span class="text-yellow-400 font-bold">{{ $credits }} credits</span> have been added to your account.</p>
        <a href="{{ route('dashboard') }}"
            class="inline-block px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl font-bold hover:from-blue-500 hover:to-purple-500 transition-all">
            Start Generating Emails →
        </a>
    </div>
</body>
</html>