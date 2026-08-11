<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe — ColdSpark AI</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-gray-900 border border-gray-800 rounded-2xl p-8 text-center">
        @if($done)
            <div class="text-5xl mb-4">✅</div>
            <h1 class="text-xl font-bold mb-2">You're unsubscribed</h1>
            <p class="text-gray-500 text-sm">You won't receive any further emails from this sender. It may take a few minutes for any in-flight follow-ups to stop.</p>
        @elseif(!$found)
            <div class="text-5xl mb-4">⚠️</div>
            <h1 class="text-xl font-bold mb-2">Link not recognized</h1>
            <p class="text-gray-500 text-sm">This unsubscribe link is invalid or has expired.</p>
        @else
            <div class="text-5xl mb-4">✉️</div>
            <h1 class="text-xl font-bold mb-2">Unsubscribe {{ $prospect->email }}?</h1>
            <p class="text-gray-500 text-sm mb-6">You'll stop receiving all future emails from this sender.</p>
            <form method="POST" action="{{ route('campaign.unsubscribe', ['token' => $token]) }}">
                <button type="submit"
                    class="px-6 py-3 bg-red-900 hover:bg-red-800 text-red-300 font-bold rounded-xl transition-all">
                    Confirm Unsubscribe
                </button>
            </form>
        @endif
    </div>
</body>
</html>
