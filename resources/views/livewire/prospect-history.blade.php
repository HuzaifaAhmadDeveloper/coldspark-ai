<div class="min-h-screen bg-gray-950 text-white">
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex gap-4 items-center">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white">← Generator</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm text-gray-400 hover:text-white">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Sequence History</h1>
            <input wire:model.live="search" type="text" placeholder="Search by name or company..."
                class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white w-64 focus:outline-none focus:border-blue-500">
        </div>

        @if($viewing)
        <!-- VIEW MODAL -->
        <div class="bg-gray-900 border border-gray-700 rounded-2xl p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="font-bold text-lg">{{ $viewing->prospect->name }} — {{ $viewing->prospect->company }}</h2>
                    <span class="text-gray-500 text-sm">{{ $viewing->created_at->format('M d, Y') }} · Style: {{ ucfirst($viewing->style) }}</span>
                </div>
                <div class="flex gap-2">
                    <button wire:click="deleteSequence({{ $viewing->id }})"
                        class="px-4 py-2 bg-red-900 hover:bg-red-800 text-red-300 rounded-xl text-sm">🗑 Delete</button>
                    <button wire:click="closeView"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-sm">✕ Close</button>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl p-4 mb-3">
                <div class="text-purple-400 text-xs font-bold mb-2">SUBJECTS</div>
                <div class="text-sm text-gray-300 mb-1">A: {{ $viewing->subject1 }}</div>
                <div class="text-sm text-gray-300">B: {{ $viewing->subject2 }}</div>
            </div>

            @foreach(['email1' => 'EMAIL 1 — OPENER', 'email2' => 'EMAIL 2 — FOLLOW-UP', 'email3' => 'EMAIL 3 — FINAL'] as $key => $label)
            <div class="bg-gray-800 rounded-xl p-4 mb-3">
                <div class="text-blue-400 text-xs font-bold mb-2">{{ $label }}</div>
                <pre class="text-gray-300 text-sm whitespace-pre-wrap font-sans">{{ $viewing->$key }}</pre>
            </div>
            @endforeach
        </div>
        @endif

        <!-- SEQUENCES LIST -->
        @forelse($sequences as $seq)
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 mb-4 hover:border-gray-600 transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-bold text-white">{{ $seq->prospect->name }}</div>
                    <div class="text-gray-500 text-sm">{{ $seq->prospect->company }} · {{ $seq->prospect->role }}</div>
                    <div class="text-gray-600 text-xs mt-1">{{ $seq->created_at->diffForHumans() }} · {{ ucfirst($seq->style) }} style</div>
                </div>
                <button wire:click="viewSequence({{ $seq->id }})"
                    class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-blue-300 rounded-xl text-sm font-semibold">
                    View →
                </button>
            </div>
        </div>
        @empty
        <div class="text-center py-20 text-gray-600">
            <div class="text-5xl mb-4">📭</div>
            <div class="text-lg font-semibold">No sequences yet</div>
            <div class="text-sm mt-2">Generate your first email sequence to see it here</div>
        </div>
        @endforelse

        <div class="mt-4">{{ $sequences->links() }}</div>
    </div>
</div>