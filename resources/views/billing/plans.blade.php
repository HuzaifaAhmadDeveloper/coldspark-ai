<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColdSpark AI — Pricing</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="bg-gray-950 text-white min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm bg-gray-800 border border-gray-700 px-3 py-1 rounded-lg">
                Credits: <span class="text-yellow-400 font-bold">{{ $credits }}</span>
            </span>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white">← Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-12">

        <!-- HEADER -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Choose Your Plan</h1>
            <p class="text-gray-400 text-lg">Scale your outreach with the right amount of credits</p>
            @if(session('message'))
            <div class="mt-4 bg-yellow-900 border border-yellow-700 rounded-xl px-6 py-3 text-yellow-300 inline-block">
                {{ session('message') }}
            </div>
            @endif
        </div>

        <!-- CURRENT PLAN BADGE -->
        <div class="text-center mb-8">
            <span class="bg-blue-900 border border-blue-700 text-blue-300 px-4 py-2 rounded-full text-sm font-semibold">
                Current Plan: {{ $plan }} — {{ $credits }} credits remaining
            </span>
        </div>

        <!-- PLANS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- BASIC -->
            <div class="bg-gray-900 border {{ $plan === 'Basic' ? 'border-blue-500' : 'border-gray-800' }} rounded-2xl p-6 relative">
                @if($plan === 'Basic')
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full font-bold">CURRENT</div>
                @endif
                <div class="text-center mb-6">
                    <div class="text-3xl mb-2">🌱</div>
                    <h2 class="text-xl font-bold">Basic</h2>
                    <div class="text-3xl font-bold mt-2">$0<span class="text-gray-500 text-base font-normal">/mo</span></div>
                </div>
                <ul class="space-y-3 mb-6">
                    @foreach(['10 credits/month', 'Single email generator', 'Email history', 'All writing styles'] as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-400">
                        <span class="text-green-400">✓</span> {{ $feature }}
                    </li>
                    @endforeach
                    @foreach(['Bulk CSV generator', 'Priority support'] as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-600">
                        <span class="text-gray-700">✗</span> {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                <button disabled class="w-full py-3 rounded-xl font-bold bg-gray-800 text-gray-500 cursor-not-allowed">
                    Free Plan
                </button>
            </div>

            <!-- PRO -->
            <div class="bg-gray-900 border {{ $onPro ? 'border-blue-500' : 'border-purple-700' }} rounded-2xl p-6 relative transform {{ !$onPro ? 'scale-105' : '' }}">
                @if($onPro)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full font-bold">CURRENT</div>
                @else
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-purple-600 text-white text-xs px-3 py-1 rounded-full font-bold">POPULAR</div>
                @endif
                <div class="text-center mb-6">
                    <div class="text-3xl mb-2">🚀</div>
                    <h2 class="text-xl font-bold">Pro</h2>
                    <div class="text-3xl font-bold mt-2">$9<span class="text-gray-500 text-base font-normal">/mo</span></div>
                </div>
                <ul class="space-y-3 mb-6">
                    @foreach(['100 credits/month', 'Single email generator', 'Bulk CSV generator', 'Email history', 'All writing styles', 'Priority support'] as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <span class="text-green-400">✓</span> {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                @if($onPro)
                <form method="POST" action="{{ route('billing.cancel') }}">
                    @csrf
                    <button class="w-full py-3 rounded-xl font-bold bg-red-900 hover:bg-red-800 text-red-300 transition-all">
                        Cancel Plan
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ env('STRIPE_PRO_PRICE') }}">
                    <button class="w-full py-3 rounded-xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 transition-all">
                        Upgrade to Pro →
                    </button>
                </form>
                @endif
            </div>

            <!-- BUSINESS -->
            <div class="bg-gray-900 border {{ $onBusiness ? 'border-blue-500' : 'border-yellow-700' }} rounded-2xl p-6 relative">
                @if($onBusiness)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full font-bold">CURRENT</div>
                @endif
                <div class="text-center mb-6">
                    <div class="text-3xl mb-2">💼</div>
                    <h2 class="text-xl font-bold">Business</h2>
                    <div class="text-3xl font-bold mt-2">$29<span class="text-gray-500 text-base font-normal">/mo</span></div>
                </div>
                <ul class="space-y-3 mb-6">
                    @foreach(['500 credits/month', 'Single email generator', 'Bulk CSV generator (50 prospects)', 'Full email history', 'All writing styles', 'Priority support', 'Early access to new features'] as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <span class="text-green-400">✓</span> {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                @if($onBusiness)
                <form method="POST" action="{{ route('billing.cancel') }}">
                    @csrf
                    <button class="w-full py-3 rounded-xl font-bold bg-red-900 hover:bg-red-800 text-red-300 transition-all">
                        Cancel Plan
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ env('STRIPE_BUSINESS_PRICE') }}">
                    <button class="w-full py-3 rounded-xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 transition-all">
                        Upgrade to Business →
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- MANAGE BILLING -->
        @if($plan !== 'Basic')
        <div class="text-center mt-8">
            <a href="{{ route('billing.portal') }}"
                class="text-sm text-gray-400 hover:text-white underline">
                Manage billing & invoices →
            </a>
        </div>
        @endif

    </div>
    @livewireScripts
</body>
</html>