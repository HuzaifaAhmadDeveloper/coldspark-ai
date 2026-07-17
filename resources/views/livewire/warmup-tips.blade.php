<div class="min-h-screen bg-gray-950 text-white">
    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">✉ Generator</a>
            <a href="{{ route('bulk') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">📂 Bulk CSV</a>
            <a href="{{ route('history') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🕐 History</a>
            <a href="{{ route('team') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">👥 Team</a>
            <a href="{{ route('warmup') }}" class="text-sm px-3 py-1 rounded-lg bg-orange-900 text-orange-300">🔥 Warmup</a>
            <a href="{{ route('billing.plans') }}" class="text-sm px-3 py-1 rounded-lg bg-yellow-900 text-yellow-400 font-semibold">⚡ Upgrade</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">

        <!-- HEADER -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">🔥 Email Warmup Guide</h1>
            <p class="text-gray-500">Everything you need to know to land in the inbox — not the spam folder.</p>
        </div>

        <!-- STATS BAR -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            @foreach([['📬','93%','Average inbox rate with proper warmup'],['⚡','3x','More replies with personalization'],['📈','70%','Replies come from follow-ups'],['🎯','2%','Max acceptable bounce rate']] as $stat)
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 text-center">
                <div class="text-2xl mb-1">{{ $stat[0] }}</div>
                <div class="text-2xl font-bold text-blue-400">{{ $stat[1] }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $stat[2] }}</div>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <!-- LEFT: CATEGORY TABS -->
            <div class="space-y-2">
                @foreach($categories as $i => $cat)
                <button wire:click="setCategory({{ $i }})"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all {{ $activeCategory === $i ? 'bg-blue-900 border border-blue-600 text-white' : 'bg-gray-900 border border-gray-800 text-gray-400 hover:border-gray-600' }}">
                    <span class="text-lg mr-2">{{ $cat['icon'] }}</span>
                    <span class="font-semibold text-sm">{{ $cat['title'] }}</span>
                    <div class="text-xs text-gray-500 mt-1 ml-7">{{ count($cat['tips']) }} tips</div>
                </button>
                @endforeach
            </div>

            <!-- RIGHT: TIPS -->
            <div class="lg:col-span-3 space-y-4">
                @foreach($categories[$activeCategory]['tips'] as $tip)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center">
                        <h3 class="font-bold text-white text-lg">{{ $tip['title'] }}</h3>
                        <span class="text-xs px-3 py-1 rounded-full font-bold
                            {{ $tip['priority'] === 'Critical' ? 'bg-red-900 text-red-400' :
                               ($tip['priority'] === 'Important' ? 'bg-yellow-900 text-yellow-400' : 'bg-green-900 text-green-400') }}">
                            {{ $tip['priority'] }}
                        </span>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-300 text-sm leading-relaxed mb-4">{{ $tip['desc'] }}</p>

                        <!-- CODE/EXAMPLE BOX -->
                        <div class="bg-gray-800 rounded-xl px-4 py-3 mb-4">
                            <div class="text-gray-500 text-xs mb-1 font-mono">EXAMPLE / ACTION</div>
                            <div class="text-blue-300 text-sm font-mono">{{ $tip['code'] }}</div>
                        </div>

                        <!-- IMPACT -->
                        <div class="text-sm {{ str_contains($tip['impact'], '🔴') ? 'text-red-400' : (str_contains($tip['impact'], '🟡') ? 'text-yellow-400' : 'text-green-400') }}">
                            {{ $tip['impact'] }}
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- PROGRESS NOTE -->
                <div class="bg-gray-900 border border-blue-900 rounded-2xl p-5 text-center">
                    <div class="text-blue-400 font-bold mb-2">
                        {{ $activeCategory + 1 }} of {{ count($categories) }} categories
                    </div>
                    <div class="flex justify-center gap-2">
                        @foreach($categories as $i => $cat)
                        <div class="w-8 h-2 rounded-full {{ $i <= $activeCategory ? 'bg-blue-500' : 'bg-gray-700' }}"></div>
                        @endforeach
                    </div>
                    @if($activeCategory < count($categories) - 1)
                    <button wire:click="setCategory({{ $activeCategory + 1 }})"
                        class="mt-4 px-6 py-2 bg-blue-800 hover:bg-blue-700 text-blue-300 rounded-xl text-sm font-semibold transition-all">
                        Next: {{ $categories[$activeCategory + 1]['title'] }} →
                    </button>
                    @else
                    <div class="mt-4 text-green-400 font-bold">🎉 You've completed the warmup guide!</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>