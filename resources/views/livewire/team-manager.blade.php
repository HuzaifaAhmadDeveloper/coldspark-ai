<div class="min-h-screen bg-gray-950 text-white">
    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-sm">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white">← Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold mb-2">👥 Team Management</h1>
        <p class="text-gray-500 text-sm mb-8">Create a team or join one to share email sequences with your colleagues.</p>

        @if($success)
        <div class="bg-green-900 border border-green-700 rounded-xl px-5 py-3 text-green-300 text-sm mb-6">✓ {{ $success }}</div>
        @endif
        @if($error)
        <div class="bg-red-900 border border-red-700 rounded-xl px-5 py-3 text-red-300 text-sm mb-6">{{ $error }}</div>
        @endif

        @if(!$team)
        <!-- CREATE OR JOIN -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- CREATE -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">CREATE A TEAM</h2>
                <div class="mb-4">
                    <label class="text-gray-500 text-xs mb-1 block">Team Name</label>
                    <input wire:model="teamName" type="text" placeholder="e.g. Sales Squad"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    @error('teamName') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                </div>
                <button wire:click="createTeam"
                    class="w-full py-3 rounded-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 transition-all">
                    🚀 Create Team
                </button>
            </div>

            <!-- JOIN -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-green-400 font-bold text-xs tracking-widest mb-4">JOIN A TEAM</h2>
                <div class="mb-4">
                    <label class="text-gray-500 text-xs mb-1 block">Invite Code</label>
                    <input wire:model="inviteCode" type="text" placeholder="e.g. AB12CD34" maxlength="8"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white uppercase focus:outline-none focus:border-green-500">
                    @error('inviteCode') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                </div>
                <button wire:click="joinTeam"
                    class="w-full py-3 rounded-xl font-bold bg-green-800 hover:bg-green-700 text-green-300 transition-all">
                    ➜ Join Team
                </button>
            </div>
        </div>

        @else
        <!-- TEAM DASHBOARD -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white">{{ $team->name }}</h2>
                    <span class="text-gray-500 text-sm">{{ $members->count() }} member{{ $members->count() > 1 ? 's' : '' }}</span>
                </div>
                <button wire:click="leaveTeam"
                    class="text-xs px-4 py-2 bg-red-900 hover:bg-red-800 text-red-300 rounded-xl transition-all">
                    {{ $team->owner_id === auth()->id() ? '🗑 Dissolve Team' : '← Leave Team' }}
                </button>
            </div>

            <!-- INVITE CODE -->
            @if($team->owner_id === auth()->id())
            <div class="bg-gray-800 rounded-xl p-4 mb-6">
                <div class="text-gray-500 text-xs mb-2">INVITE CODE — Share this with teammates</div>
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold font-mono text-blue-400 tracking-widest">{{ $team->invite_code }}</span>
                    <button wire:click="regenerateCode"
                        class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg">
                        🔄 Regenerate
                    </button>
                    <button onclick="navigator.clipboard.writeText('{{ $team->invite_code }}').then(() => alert('Copied!'))"
                        class="text-xs px-3 py-1 bg-blue-900 hover:bg-blue-800 text-blue-300 rounded-lg">
                        Copy
                    </button>
                </div>
            </div>
            @endif

            <!-- MEMBERS LIST -->
            <div>
                <div class="text-gray-500 text-xs mb-3 tracking-widest">TEAM MEMBERS</div>
                <div class="space-y-3">
                    @foreach($members as $member)
                    <div class="flex items-center justify-between bg-gray-800 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr($member->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-white text-sm font-semibold">{{ $member->user->name }}</div>
                                <div class="text-gray-500 text-xs">{{ $member->user->email }}</div>
                            </div>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full {{ $member->role === 'owner' ? 'bg-yellow-900 text-yellow-400' : 'bg-gray-700 text-gray-400' }}">
                            {{ ucfirst($member->role) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- SHARED SEQUENCES -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <div class="text-blue-400 font-bold text-xs tracking-widest mb-4">TEAM SEQUENCES</div>
            @php
                $teamUserIds = $members->pluck('user_id')->toArray();
                $teamSequences = \App\Models\Sequence::with(['prospect','user'])
                    ->whereIn('user_id', $teamUserIds)
                    ->latest()->take(20)->get();
            @endphp
            @forelse($teamSequences as $seq)
            <div class="border-b border-gray-800 py-3 flex justify-between items-center">
                <div>
                    <div class="text-white text-sm font-semibold">{{ $seq->prospect->name }} — {{ $seq->prospect->company }}</div>
                    <div class="text-gray-500 text-xs">By {{ $seq->user->name }} · {{ $seq->created_at->diffForHumans() }}</div>
                </div>
                <span class="text-xs px-2 py-1 bg-gray-800 text-gray-400 rounded-lg">{{ ucfirst($seq->style) }}</span>
            </div>
            @empty
            <div class="text-center text-gray-600 py-8">
                <div class="text-3xl mb-2">📭</div>
                <div class="text-sm">No team sequences yet</div>
            </div>
            @endforelse
        </div>
        @endif
    </div>
</div>