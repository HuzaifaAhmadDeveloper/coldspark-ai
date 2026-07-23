<div class="min-h-screen bg-gray-950 text-white">
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">✉ Generator</a>
            <a href="{{ route('bulk') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">📂 Bulk CSV</a>
            <a href="{{ route('crm') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🔗 CRM</a>
            <a href="{{ route('history') }}" class="text-sm px-3 py-1 rounded-lg bg-green-900 text-green-300">🕐 History</a>
            <a href="{{ route('team') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">👥 Team</a>
            <a href="{{ route('warmup') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🔥 Warmup</a>
            <a href="{{ route('billing.plans') }}" class="text-sm px-3 py-1 rounded-lg bg-yellow-900 text-yellow-400 font-semibold">⚡ Upgrade</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- STATS -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-3xl font-bold text-white">{{ $stats['total'] }}</div>
                <div class="text-gray-500 text-sm mt-1">Total Sequences</div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-3xl font-bold text-green-400">{{ $stats['replied'] }}</div>
                <div class="text-gray-500 text-sm mt-1">Got Replies</div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-3xl font-bold text-blue-400">
                    {{ $stats['total'] > 0 ? round(($stats['replied'] / $stats['total']) * 100) : 0 }}%
                </div>
                <div class="text-gray-500 text-sm mt-1">Reply Rate</div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="flex justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold">Sequence History</h1>
            <div class="flex gap-3">
                <div class="flex gap-2">
                    @foreach(['all' => 'All', 'replied' => '✓ Replied', 'no_reply' => '⏳ No Reply'] as $val => $label)
                    <button wire:click="$set('filterReply', '{{ $val }}')"
                        class="text-xs px-3 py-1 rounded-lg transition-all {{ $filterReply === $val ? 'bg-blue-900 text-blue-300' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input wire:model.live="search" type="text" placeholder="Search name or company..."
                    class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white w-56 focus:outline-none focus:border-blue-500">
            </div>
        </div>

        <!-- VIEW MODAL -->
        @if($viewing)
        <div class="bg-gray-900 border border-gray-700 rounded-2xl p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="font-bold text-lg">{{ $viewing->prospect->name }} — {{ $viewing->prospect->company }}</h2>
                    <span class="text-gray-500 text-sm">{{ $viewing->created_at->format('M d, Y') }} · {{ ucfirst($viewing->style) }} style</span>
                </div>
                <div class="flex gap-2">
                    @if($viewing->replied)
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        {{ $viewing->reply_status === 'positive' ? 'bg-green-900 text-green-400' :
                           ($viewing->reply_status === 'negative' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400') }}">
                        ✓ {{ ucfirst($viewing->reply_status) }} Reply
                    </span>
                    @endif
                    <button wire:click="deleteSequence({{ $viewing->id }})"
                        class="px-3 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded-xl text-sm">🗑</button>
                    <button wire:click="closeView"
                        class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-sm">✕</button>
                </div>
            </div>

            <!-- REPLY TRACKING SECTION -->
            <div class="bg-gray-800 rounded-xl p-4 mb-4">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-blue-400 text-xs font-bold tracking-widest">📬 REPLY TRACKING</span>
                    <div class="flex gap-2">
                        @if(!$viewing->replied)
                        <button wire:click="$set('showReplyForm', true)"
                            class="text-xs px-3 py-1 bg-green-800 hover:bg-green-700 text-green-300 rounded-lg">
                            ✓ Mark as Replied
                        </button>
                        @else
                        <button wire:click="markNoReply"
                            class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg">
                            ✕ Remove Reply
                        </button>
                        @endif
                    </div>
                </div>

                @if($viewing->replied)
                <div class="text-sm text-gray-300">
                    <div class="mb-1">Replied: <span class="text-green-400">{{ $viewing->replied_at?->format('M d, Y h:i A') }}</span></div>
                    <div class="mb-1">Status: <span class="text-yellow-400 font-semibold">{{ ucfirst($viewing->reply_status) }}</span></div>
                    @if($viewing->reply_notes)
                    <div>Notes: <span class="text-gray-400">{{ $viewing->reply_notes }}</span></div>
                    @endif
                </div>
                @else
                <div class="text-gray-600 text-sm">No reply tracked yet.</div>
                @endif

                @if($showReplyForm)
                <div class="mt-4 border-t border-gray-700 pt-4">
                    <div class="mb-3">
                        <label class="text-gray-500 text-xs mb-1 block">Reply Sentiment</label>
                        <div class="flex gap-2">
                            @foreach(['positive' => '✓ Positive', 'negative' => '✗ Negative', 'neutral' => '~ Neutral'] as $val => $label)
                            <button wire:click="$set('replyStatus', '{{ $val }}')"
                                class="text-xs px-3 py-1 rounded-lg transition-all {{ $replyStatus === $val ? 'bg-blue-900 text-blue-300 border border-blue-500' : 'bg-gray-700 text-gray-400' }}">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-gray-500 text-xs mb-1 block">Notes (optional)</label>
                        <textarea wire:model="replyNotes" rows="2" placeholder="What did they say?"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <button wire:click="markReplied"
                        class="px-4 py-2 bg-green-700 hover:bg-green-600 text-green-200 rounded-xl text-sm font-bold">
                        ✓ Save Reply
                    </button>
                </div>
                @endif
            </div>

            <!-- EMAIL SUBJECTS -->
            <div class="bg-gray-800 rounded-xl p-4 mb-3">
                <div class="text-purple-400 text-xs font-bold mb-2">SUBJECTS</div>
                <div class="text-sm text-gray-300 mb-1">A: {{ $viewing->subject1 }}</div>
                <div class="text-sm text-gray-300">B: {{ $viewing->subject2 }}</div>
            </div>

            <!-- EMAIL CARDS WITH GMAIL + OUTLOOK BUTTONS -->
            @foreach(['email1' => 'EMAIL 1 — OPENER', 'email2' => 'EMAIL 2 — FOLLOW-UP', 'email3' => 'EMAIL 3 — FINAL'] as $key => $label)
            <div class="bg-gray-800 rounded-xl p-4 mb-3">
                <div class="flex justify-between items-center mb-2">
                    <div class="text-blue-400 text-xs font-bold">{{ $label }}</div>
                    <div class="flex gap-2">
                        <button onclick="navigator.clipboard.writeText(`{{ addslashes($viewing->$key) }}`).then(() => alert('Copied!'))"
                            class="text-xs px-3 py-1 bg-blue-900 hover:bg-blue-800 text-blue-300 rounded-lg transition-all">
                            📋 Copy
                        </button>
                        <a href="#" onclick="openGmail('{{ addslashes($viewing->subject1) }}', '{{ addslashes($viewing->$key) }}'); return false;"
                            class="text-xs px-3 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded-lg transition-all">
                            📧 Gmail
                        </a>
                        <a href="#" onclick="openOutlook('{{ addslashes($viewing->subject1) }}', '{{ addslashes($viewing->$key) }}'); return false;"
                            class="text-xs px-3 py-1 bg-blue-900 hover:bg-blue-700 text-blue-200 rounded-lg transition-all">
                            📨 Outlook
                        </a>
                    </div>
                </div>
                <div class="text-gray-300 text-sm leading-relaxed font-sans space-y-3">
    @foreach(explode("\n\n", $viewing->$key ?? '') as $paragraph)
        @if(trim($paragraph))
            <p>{!! nl2br(e(trim($paragraph))) !!}</p>
        @endif
    @endforeach
</div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- SEQUENCES LIST -->
        @forelse($sequences as $seq)
        <div class="bg-gray-900 border {{ $seq->replied ? 'border-green-900' : 'border-gray-800' }} rounded-2xl p-5 mb-4 hover:border-gray-600 transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="font-bold text-white">{{ $seq->prospect->name }}</div>
                        @if($seq->replied)
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $seq->reply_status === 'positive' ? 'bg-green-900 text-green-400' :
                               ($seq->reply_status === 'negative' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400') }}">
                            ✓ {{ ucfirst($seq->reply_status) }}
                        </span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-800 text-gray-500">⏳ No reply</span>
                        @endif
                    </div>
                    <div class="text-gray-500 text-sm">{{ $seq->prospect->company }} · {{ $seq->prospect->role }}</div>
                    <div class="text-gray-600 text-xs mt-1">{{ $seq->created_at->diffForHumans() }} · {{ ucfirst($seq->style) }}</div>
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

    <script>
    function openGmail(subject, body) {
        const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.open(gmailUrl, '_blank');
    }
    function openOutlook(subject, body) {
        const outlookUrl = `https://outlook.live.com/mail/0/deeplink/compose?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.open(outlookUrl, '_blank');
    }
    </script>
</div>