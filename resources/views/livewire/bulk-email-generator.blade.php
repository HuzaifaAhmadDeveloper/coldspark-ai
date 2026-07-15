<div class="min-h-screen bg-gray-950 text-white">
    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-400">Credits: <span class="text-yellow-400 font-bold">{{ $credits }}</span></span>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white">← Generator</a>
            <a href="{{ route('history') }}" class="text-sm text-gray-400 hover:text-white">History</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm text-gray-400 hover:text-white">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-1">📂 Bulk Email Generator</h1>
            <p class="text-gray-500 text-sm">Upload a CSV with multiple prospects — AI generates personalized sequences for all of them.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: SETTINGS -->
            <div class="space-y-5">

                <!-- CSV FORMAT GUIDE -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">CSV FORMAT</h2>
                    <p class="text-gray-500 text-xs mb-3">Required columns in your CSV:</p>
                    <div class="space-y-1">
                        @foreach(['name ✅','company ✅','role ✅','industry','pain_point','note'] as $col)
                        <div class="text-xs font-mono bg-gray-800 px-3 py-1 rounded text-blue-300">{{ $col }}</div>
                        @endforeach
                    </div>
                    <a href="{{ route('bulk.sample') }}" class="mt-3 block text-center text-xs text-green-400 hover:text-green-300 bg-green-900 rounded-lg py-2">
                        ⬇ Download Sample CSV
                    </a>
                </div>

                <!-- UPLOAD -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">UPLOAD CSV</h2>
                    <label class="block w-full cursor-pointer">
                        <div class="border-2 border-dashed border-gray-700 rounded-xl p-6 text-center hover:border-blue-500 transition-all">
                            <div class="text-3xl mb-2">📁</div>
                            <div class="text-sm text-gray-400">Click to upload CSV</div>
                            <div class="text-xs text-gray-600 mt-1">Max 50 prospects, 2MB</div>
                        </div>
                        <input wire:model="csvFile" type="file" accept=".csv" class="hidden">
                    </label>
                    @error('csvFile') <p class="text-red-400 text-xs mt-2">{{ $message }}</p> @enderror

                    @if($total > 0)
                    <div class="mt-3 bg-green-900 border border-green-700 rounded-xl px-4 py-2 text-green-400 text-sm text-center">
                        ✓ {{ $total }} prospects loaded
                    </div>
                    @endif
                </div>

                <!-- YOUR OFFER -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">YOUR OFFER</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">What you're selling *</label>
                            <input wire:model="offer" type="text" placeholder="AI sales platform"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">Value Proposition *</label>
                            <input wire:model="value_prop" type="text" placeholder="We help close 3x more deals"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">CTA</label>
                            <input wire:model="cta" type="text" placeholder="Book a 15-min call"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- STYLE -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">EMAIL STYLE</h2>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['direct'=>'🎯 Direct','friendly'=>'😊 Friendly','formal'=>'💼 Formal','witty'=>'⚡ Witty'] as $key=>$label)
                        <div wire:click="$set('style','{{ $key }}')"
                            class="p-2 rounded-xl cursor-pointer text-center text-sm border transition-all {{ $style===$key ? 'bg-blue-900 border-blue-500 text-blue-300 font-bold' : 'bg-gray-800 border-gray-700 text-gray-400 hover:border-gray-500' }}">
                            {{ $label }}
                        </div>
                        @endforeach
                    </div>
                </div>

                @if($error)
                <div class="bg-red-900 border border-red-700 rounded-xl p-4 text-red-300 text-sm">{{ $error }}</div>
                @endif

                <button wire:click="process" wire:loading.attr="disabled"
                    @if($total === 0 || $processing) disabled @endif
                    class="w-full py-4 rounded-xl font-bold text-base bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="process">
                        ✨ Generate {{ $total > 0 ? $total : '' }} Sequences
                    </span>
                    <span wire:loading wire:target="process">⏳ Processing...</span>
                </button>
            </div>

            <!-- RIGHT: RESULTS -->
            <div class="lg:col-span-2">

                <!-- PROGRESS BAR -->
                @if($processing || $done)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 mb-5">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-semibold text-white">
                            {{ $processing ? 'Processing...' : '✅ Complete!' }}
                        </span>
                        <span class="text-sm text-gray-400">{{ $progress }} / {{ $total }}</span>
                    </div>
                    <div class="bg-gray-800 rounded-full h-3 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-300 rounded-full"
                            style="width: {{ $total > 0 ? ($progress/$total*100) : 0 }}%"></div>
                    </div>
                    @if($done)
                    <div class="flex justify-between mt-3 text-sm">
                        <span class="text-green-400">✓ {{ $progress - $failed }} successful</span>
                        @if($failed > 0)<span class="text-red-400">✗ {{ $failed }} failed</span>@endif
                        <a href="{{ route('history') }}" class="text-blue-400 hover:text-blue-300 font-semibold">View in History →</a>
                    </div>
                    @endif
                </div>
                @endif

                <!-- PREVIEW TABLE -->
                @if(!empty($preview) && !$done)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden mb-5">
                    <div class="px-5 py-3 border-b border-gray-800 flex justify-between items-center">
                        <span class="text-blue-400 font-bold text-xs tracking-widest">PROSPECT PREVIEW</span>
                        <span class="text-gray-500 text-xs">{{ $total }} rows loaded</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-800">
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">#</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Name</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Company</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($preview, 0, 10) as $i => $row)
                                <tr class="border-b border-gray-800 hover:bg-gray-800 transition-colors">
                                    <td class="px-4 py-2 text-gray-600">{{ $i+1 }}</td>
                                    <td class="px-4 py-2 text-white">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $row['company'] }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $row['role'] }}</td>
                                </tr>
                                @endforeach
                                @if($total > 10)
                                <tr><td colspan="4" class="px-4 py-2 text-center text-gray-600 text-xs">... and {{ $total - 10 }} more</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- RESULTS TABLE -->
                @if(!empty($results))
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-800 flex justify-between items-center">
                        <span class="text-blue-400 font-bold text-xs tracking-widest">GENERATED SEQUENCES</span>
                        @if($done)
                        <a href="{{ route('bulk.export', $jobId) }}"
                            class="text-xs px-4 py-1 bg-green-800 hover:bg-green-700 text-green-300 rounded-lg font-semibold transition-all">
                            ⬇ Export CSV
                        </a>
                        @endif
                    </div>
                    <div class="divide-y divide-gray-800">
                        @foreach($results as $r)
                        <div class="px-5 py-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-semibold text-white">{{ $r['name'] }}</span>
                                    <span class="text-gray-500 text-sm ml-2">{{ $r['company'] }}</span>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full {{ $r['status']==='success' ? 'bg-green-900 text-green-400' : 'bg-red-900 text-red-400' }}">
                                    {{ $r['status']==='success' ? '✓ Done' : '✗ Failed' }}
                                </span>
                            </div>
                            @if($r['status']==='success')
                            <div class="text-xs text-gray-500 mb-1">Subject: {{ $r['subject1'] ?? '' }}</div>
                            <div class="text-xs text-gray-600 line-clamp-2">{{ Str::limit($r['email1'] ?? '', 120) }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- EMPTY STATE -->
                @if(empty($preview) && !$processing)
                <div class="flex flex-col items-center justify-center h-80 text-gray-600">
                    <div class="text-6xl mb-4">📂</div>
                    <div class="text-lg font-semibold mb-2">Upload a CSV to get started</div>
                    <div class="text-sm">Download the sample CSV to see the required format</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>