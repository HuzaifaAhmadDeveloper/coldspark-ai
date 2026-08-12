<div class="min-h-screen bg-gray-950 text-white" wire:poll.10s>
    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">✉ Generator</a>
            <a href="{{ route('bulk') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">📂 Bulk</a>
            <a href="{{ route('campaigns') }}" class="text-sm px-3 py-1 rounded-lg bg-green-900 text-green-300">📣 Campaigns</a>
            <a href="{{ route('history') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🕐 History</a>
            <a href="{{ route('team') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">👥 Team</a>
            <a href="{{ route('warmup') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🔥 Warmup</a>
            <a href="{{ route('billing.plans') }}" class="text-sm px-3 py-1 rounded-lg bg-yellow-900 text-yellow-400 font-semibold">⚡ Upgrade</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold">📣 Campaign Manager</h1>
                <p class="text-gray-500 text-sm mt-1">Manage your email outreach campaigns</p>
            </div>
            <a href="{{ route('campaign.create') }}"
                class="px-5 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all text-sm">
                + New Campaign
            </a>
        </div>

        <!-- LIVE STATS -->
        <div class="grid grid-cols-4 gap-4 mb-4">
            @foreach([
                ['📣', $stats['total'], 'Total Campaigns', 'gray'],
                ['🟢', $stats['active'], 'Active', 'green'],
                ['⏳', $stats['scheduled'], 'Scheduled', 'yellow'],
                ['📧', $stats['sent'], 'Sent', 'blue'],
            ] as [$icon, $val, $label, $color])
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-2xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        <div class="grid grid-cols-4 gap-4 mb-4">
            @foreach([
                ['📬', $stats['delivered'], 'Delivered', 'green'],
                ['👁️', $stats['opened'], 'Opened', 'yellow'],
                ['💬', $stats['replies'], 'Replies', 'purple'],
                ['⚠️', $stats['bounced'], 'Bounced', 'red'],
            ] as [$icon, $val, $label, $color])
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-2xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        <div class="grid grid-cols-4 gap-4 mb-4">
            @foreach([
                ['✅', $stats['completed'], 'Completed', 'blue'],
                ['🔕', $stats['unsubscribed'], 'Unsubscribed', 'gray'],
                ['📬', $stats['delivery_tracking_connected'] ? $stats['delivery_rate'].'%' : '—', 'Delivery Rate', 'green'],
                ['📈', $stats['delivery_tracking_connected'] ? $stats['open_rate'].'%' : '—', 'Open Rate', 'green'],
            ] as [$icon, $val, $label, $color])
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center">
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-2xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        <div class="grid grid-cols-4 gap-4 mb-8">
            @foreach([
                ['🖱️', $stats['delivery_tracking_connected'] ? $stats['click_rate'].'%' : '—', 'Click Rate', 'indigo'],
                ['📊', $stats['delivery_tracking_connected'] ? $stats['reply_rate'].'%' : '—', 'Reply Rate', 'purple'],
                ['⚠️', $stats['bounce_rate'].'%', 'Bounce Rate', 'red'],
                ['🔕', $stats['delivery_tracking_connected'] ? $stats['unsubscribe_rate'].'%' : '—', 'Unsubscribe Rate', 'gray'],
            ] as [$icon, $val, $label, $color])
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-center" @if(!$stats['delivery_tracking_connected'] && $label !== 'Bounce Rate') title="Delivery tracking isn't connected yet (SES event webhook not configured) — this rate can't be calculated." @endif>
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-2xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        @unless($stats['delivery_tracking_connected'])
        <div class="bg-yellow-900/30 border border-yellow-800 rounded-xl px-4 py-3 mb-8 text-xs text-yellow-400">
            ⚠️ Delivery tracking isn't connected yet — Delivery/Open/Click/Reply/Unsubscribe rates show "—" instead of a possibly-misleading 0% until Amazon SES's event webhook is wired up (see setup docs for SES_CONFIGURATION_SET).
        </div>
        @endunless

        <!-- CAMPAIGNS LIST — each card is inline-expandable -->
        @forelse($campaigns as $campaign)
        <div class="mb-4 rounded-2xl overflow-hidden border transition-all
            {{ $viewingId === $campaign->id ? 'border-blue-600 shadow-lg shadow-blue-900/20' : ($campaign->status === 'active' ? 'border-green-900' : 'border-gray-800') }}
            bg-gray-900">

            <!-- ── CAMPAIGN ROW ── -->
            <div class="flex justify-between items-center p-5">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="font-bold text-white text-lg">{{ $campaign->name }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full font-bold
                            {{ $campaign->status === 'active' ? 'bg-green-900 text-green-400' :
                               ($campaign->status === 'scheduled' ? 'bg-purple-900 text-purple-400' :
                               ($campaign->status === 'paused' ? 'bg-yellow-900 text-yellow-400' :
                               ($campaign->status === 'completed' ? 'bg-blue-900 text-blue-400' :
                               ($campaign->status === 'failed' ? 'bg-red-900 text-red-400' :
                               ($campaign->status === 'cancelled' ? 'bg-red-900 text-red-400' : 'bg-gray-700 text-gray-400'))))) }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span>👥 {{ $campaign->total_prospects }} prospects</span>
                        <span>📧 {{ $campaign->emails_sent }} sent</span>
                        <span>💬 {{ $campaign->replies_received }} replies</span>
                        <span>📅 {{ $campaign->daily_limit }}/day · {{ $campaign->gap_minutes }}min gap</span>
                        <span>🕐 {{ $campaign->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex gap-2 items-center">
                    @if(in_array($campaign->status, ['active', 'scheduled']))
                    <button wire:click="pauseCampaign({{ $campaign->id }})"
                        class="text-xs px-3 py-1 bg-yellow-900 hover:bg-yellow-800 text-yellow-300 rounded-lg transition-all">⏸ Pause</button>
                    @elseif($campaign->status === 'paused')
                    <button wire:click="resumeCampaign({{ $campaign->id }})"
                        class="text-xs px-3 py-1 bg-green-800 hover:bg-green-700 text-green-300 rounded-lg transition-all">▶ Resume</button>
                    @endif

                    @if($viewingId === $campaign->id)
                    <button wire:click="closeView"
                        class="text-xs px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl font-semibold transition-all">
                        ✕ Close
                    </button>
                    @else
                    <button wire:click="viewCampaign({{ $campaign->id }})"
                        class="text-xs px-4 py-2 bg-blue-900 hover:bg-blue-800 text-blue-300 rounded-xl font-semibold transition-all">
                        View →
                    </button>
                    @endif
                </div>
            </div>

            <!-- ── INLINE DETAIL PANEL (shown when this campaign is selected) ── -->
            @if($viewingId === $campaign->id && $viewing)
            <div class="border-t border-gray-800">

                <!-- Action bar -->
                <div class="flex items-center justify-between px-5 py-3 bg-gray-800 border-b border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">{{ $viewing->total_prospects }} prospects · Started {{ $viewing->start_date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="flex gap-2">
                        @if(in_array($viewing->status, ['active', 'scheduled']))
                        <button wire:click="pauseCampaign({{ $viewing->id }})"
                            class="px-3 py-1 bg-yellow-900 hover:bg-yellow-800 text-yellow-300 rounded-lg text-xs font-semibold">⏸ Pause</button>
                        @elseif($viewing->status === 'paused')
                        <button wire:click="resumeCampaign({{ $viewing->id }})"
                            class="px-3 py-1 bg-green-800 hover:bg-green-700 text-green-300 rounded-lg text-xs font-semibold">▶ Resume</button>
                        @endif
                        @if(in_array($viewing->status, ['active', 'scheduled', 'paused']))
                        <button wire:click="cancelCampaign({{ $viewing->id }})"
                            class="px-3 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded-lg text-xs font-semibold">🚫 Cancel</button>
                        @endif
                        <button wire:click="deleteCampaign({{ $viewing->id }})"
                            class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-xs">🗑 Delete</button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-800 px-5 bg-gray-900">
                    @foreach(['overview' => '📊 Overview', 'emails' => '📧 Emails', 'settings' => '⚙️ Settings'] as $t => $tlabel)
                    <button wire:click="setTab('{{ $t }}')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-all {{ $tab === $t ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-300' }}">
                        {{ $tlabel }}
                    </button>
                    @endforeach
                </div>

                <!-- Tab Content -->
                <div class="p-5">
                    @if($tab === 'overview')
                    @php
                        $trackingConnected = $viewing->emails_sent === 0 || $viewing->emails_delivered > 0;
                    @endphp
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        @foreach([
                            ['📧', $viewing->emails_sent, 'Sent', 'blue'],
                            ['📬', $viewing->emails_delivered, 'Delivered', 'green'],
                            ['👁️', $viewing->emails_opened, 'Opened', 'yellow'],
                            ['💬', $viewing->replies_received, 'Replies', 'green'],
                        ] as [$icon, $val, $label, $color])
                        <div class="bg-gray-800 rounded-xl p-4 text-center">
                            <div class="text-xl mb-1">{{ $icon }}</div>
                            <div class="text-xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                            <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        @foreach([
                            ['🖱️', $viewing->emails_clicked, 'Clicked', 'indigo'],
                            ['⚠️', $viewing->emails_bounced, 'Bounced', 'red'],
                            ['🚫', $viewing->cancelled_followups, 'Cancelled Follow-ups', 'gray'],
                            ['🔕', $viewing->prospects()->where('unsubscribed', true)->distinct('prospects.id')->count('prospects.id'), 'Unsubscribed', 'gray'],
                        ] as [$icon, $val, $label, $color])
                        <div class="bg-gray-800 rounded-xl p-4 text-center">
                            <div class="text-xl mb-1">{{ $icon }}</div>
                            <div class="text-xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                            <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-4 gap-4 mb-5">
                        @foreach([
                            ['📈', $trackingConnected ? $viewing->open_rate.'%' : '—', 'Open Rate', 'purple'],
                            ['🖱️', $trackingConnected ? $viewing->click_rate.'%' : '—', 'Click Rate', 'indigo'],
                            ['📊', $trackingConnected ? $viewing->reply_rate.'%' : '—', 'Reply Rate', 'purple'],
                            ['⚠️', $viewing->bounce_rate.'%', 'Bounce Rate', 'red'],
                        ] as [$icon, $val, $label, $color])
                        <div class="bg-gray-800 rounded-xl p-4 text-center">
                            <div class="text-xl mb-1">{{ $icon }}</div>
                            <div class="text-xl font-bold text-{{ $color }}-400">{{ $val }}</div>
                            <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>

                    @php
                        $progress = $viewing->total_prospects > 0
                            ? round(($viewing->emails_sent / ($viewing->total_prospects * 3)) * 100)
                            : 0;
                    @endphp
                    <div class="bg-gray-800 rounded-xl p-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-400">Campaign Progress</span>
                            <span class="text-sm text-blue-400">{{ $progress }}%</span>
                        </div>
                        <div class="bg-gray-700 rounded-full h-3">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all"
                                style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-600">
                            <span>{{ $viewing->emails_sent }} sent</span>
                            <span>{{ $viewing->total_prospects * 3 }} total emails</span>
                        </div>
                    </div>
                    @endif

                    @if($tab === 'emails')
                    <div class="space-y-2">
                        @forelse($emails as $email)
                        <div wire:key="email-{{ $email->id }}" class="flex items-center justify-between bg-gray-800 rounded-xl px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400 font-mono">E{{ $email->email_number }}</span>
                                <div>
                                    <div class="text-white text-sm font-semibold">{{ $email->prospect->name }}</div>
                                    <div class="text-gray-500 text-xs">{{ $email->prospect->company }} · {{ $email->prospect->email }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($email->opened)
                                <span class="text-xs text-yellow-400" title="Opened">👁</span>
                                @endif
                                @if($email->clicked)
                                <span class="text-xs text-indigo-400" title="Clicked">🖱️</span>
                                @endif
                                @if($email->bounced)
                                <span class="text-xs text-red-400" title="Bounced">⚠️</span>
                                @endif
                                @if($email->replied)
                                <span class="text-xs text-green-400">💬 Replied</span>
                                @elseif(in_array($email->status, ['sent','delivered']))
                                <button wire:click="markReplied({{ $email->id }})"
                                    class="text-xs px-2 py-0.5 bg-gray-700 hover:bg-green-800 text-gray-300 hover:text-green-300 rounded-lg transition-all">
                                    Mark Replied
                                </button>
                                @endif
                                <span class="text-xs text-gray-500">{{ $email->scheduled_at?->copy()->setTimezone($viewing->timezone)->format('M d h:i A') }} {{ $viewing->timezone }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                                    {{ match($email->status) {
                                        'sent'      => 'bg-green-900 text-green-400',
                                        'sending'   => 'bg-yellow-900 text-yellow-400',
                                        'scheduled' => 'bg-blue-900 text-blue-400',
                                        'failed'    => 'bg-red-900 text-red-400',
                                        'skipped'   => 'bg-gray-700 text-gray-500',
                                        default     => 'bg-gray-700 text-gray-400',
                                    } }}">
                                    {{ ucfirst($email->status) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-10 text-gray-600">
                            <div class="text-4xl mb-2">📭</div>
                            <div>No emails found</div>
                        </div>
                        @endforelse
                    </div>
                    @endif

                    @if($tab === 'settings')
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @foreach([
                            ['Daily Limit', $viewing->daily_limit . ' emails/day'],
                            ['Gap Between Emails', $viewing->gap_minutes . ' minutes'],
                            ['Follow-up 1', 'Day ' . $viewing->followup_delay_1],
                            ['Follow-up 2', 'Day ' . $viewing->followup_delay_2],
                            ['Working Hours', $viewing->working_hours_start . ' – ' . $viewing->working_hours_end],
                            ['Timezone', $viewing->timezone],
                            ['Stop on Reply', $viewing->stop_on_reply ? 'Yes' : 'No'],
                        ] as [$label, $value])
                        <div class="bg-gray-800 rounded-xl px-4 py-3 flex justify-between">
                            <span class="text-gray-500">{{ $label }}</span>
                            <span class="text-white font-semibold">{{ $value }}</span>
                        </div>
                        @endforeach
                        @if($viewing->notes)
                        <div class="col-span-2 bg-gray-800 rounded-xl px-4 py-3">
                            <span class="text-gray-500 text-xs">Notes</span>
                            <p class="text-gray-300 mt-1">{{ $viewing->notes }}</p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
        @empty
        <div class="text-center py-20 text-gray-600">
            <div class="text-6xl mb-4">📣</div>
            <div class="text-xl font-bold mb-2">No campaigns yet</div>
            <div class="text-sm mb-6">Create your first campaign to start automated outreach</div>
            <a href="{{ route('campaign.create') }}"
                class="inline-block px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-xl">
                + Create First Campaign
            </a>
        </div>
        @endforelse
    </div>
</div>