<div class="min-h-screen bg-gray-950 text-white">
    <!-- NAVBAR -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">✉</div>
            <span class="font-bold text-lg">ColdSpark <span class="text-blue-400">AI</span></span>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('campaigns') }}" class="text-sm text-gray-400 hover:text-white">← Campaigns</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- STEP INDICATOR -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                @foreach([
                    1 => '⚙️ Settings',
                    2 => '👥 Prospects',
                    3 => '✨ Generate',
                    4 => '👁️ Review',
                    5 => '🚀 Launch'
                ] as $s => $label)
                <div class="flex items-center {{ $s < 5 ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all
                            {{ $step >= $s ? 'bg-blue-600 border-blue-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-500' }}">
                            {{ $step > $s ? '✓' : $s }}
                        </div>
                        <span class="text-xs mt-1 {{ $step >= $s ? 'text-blue-400' : 'text-gray-600' }}">{{ $label }}</span>
                    </div>
                    @if($s < 5)
                    <div class="flex-1 h-0.5 mx-2 {{ $step > $s ? 'bg-blue-600' : 'bg-gray-800' }}"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        @if($error)
        <div class="bg-red-900 border border-red-700 rounded-xl p-4 text-red-300 text-sm mb-6">{{ $error }}</div>
        @endif

        <!-- ═══ STEP 1: CAMPAIGN SETTINGS ═══ -->
        @if($step === 1)
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-white mb-1">Campaign Settings</h2>
            <p class="text-gray-500 text-sm mb-6">Configure your campaign name, schedule and sending rules.</p>

            <div class="grid grid-cols-2 gap-5">
                <div class="col-span-2">
                    <label class="text-gray-400 text-xs mb-1 block">Campaign Name *</label>
                    <input wire:model="name" type="text" placeholder="e.g. Q3 SaaS Outreach"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Daily Email Limit</label>
                    <input wire:model="daily_limit" type="number" min="1" max="200"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                    <p class="text-gray-600 text-xs mt-1">Max emails to send per day</p>
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Gap Between Emails (minutes)</label>
                    <input wire:model="gap_minutes" type="number" min="1" max="120"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                    <p class="text-gray-600 text-xs mt-1">Wait time between each email send</p>
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Start Date</label>
                    <input wire:model="start_date" type="date"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Timezone</label>
                    <select wire:model="timezone"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                        <option value="Asia/Karachi">Pakistan (PKT)</option>
                        <option value="America/New_York">US Eastern</option>
                        <option value="America/Chicago">US Central</option>
                        <option value="America/Los_Angeles">US Pacific</option>
                        <option value="Europe/London">UK (GMT)</option>
                        <option value="Europe/Berlin">Central Europe</option>
                        <option value="Asia/Dubai">Dubai (GST)</option>
                        <option value="Asia/Kolkata">India (IST)</option>
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Working Hours Start</label>
                    <input wire:model="working_hours_start" type="time"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Working Hours End</label>
                    <input wire:model="working_hours_end" type="time"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Follow-up 1 Delay (days)</label>
                    <input wire:model="followup_delay_1" type="number" min="1" max="30"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                    <p class="text-gray-600 text-xs mt-1">Days after Email 1</p>
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Follow-up 2 Delay (days)</label>
                    <input wire:model="followup_delay_2" type="number" min="1" max="60"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                    <p class="text-gray-600 text-xs mt-1">Days after Email 1</p>
                </div>
                <div class="col-span-2">
                    <div class="flex items-center gap-3 bg-gray-800 rounded-xl p-4">
                        <input wire:model="stop_on_reply" type="checkbox" id="stop_on_reply"
                            class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-blue-500">
                        <div>
                            <label for="stop_on_reply" class="text-white text-sm font-semibold cursor-pointer">Stop follow-ups when prospect replies</label>
                            <p class="text-gray-500 text-xs">Automatically skip follow-ups if a reply is detected</p>
                        </div>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="text-gray-400 text-xs mb-1 block">Campaign Notes (optional)</label>
                    <textarea wire:model="notes" rows="2" placeholder="Internal notes about this campaign..."
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>
            </div>
        </div>
        @endif

        <!-- ═══ STEP 2: PROSPECTS ═══ -->
        @if($step === 2)
<div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
    <h2 class="text-xl font-bold text-white mb-1">Add Prospects</h2>
    <p class="text-gray-500 text-sm mb-6">Upload a CSV file with your prospect list.</p>

    <!-- Upload Area -->
    <div class="mb-5">
        <label for="campaignCsvInput" class="block cursor-pointer">
            <div id="campaignDropzone"
                class="border-2 border-dashed border-gray-700 rounded-2xl p-10 text-center hover:border-blue-500 transition-all">
                <div class="text-5xl mb-3">📁</div>
                <div class="text-gray-300 font-semibold mb-1" id="csv-filename">
                    {{ $csvFileName ? '✓ '.$csvFileName : 'Click to upload CSV' }}
                </div>
                <div class="text-gray-500 text-sm">Required: name, email, company, role, industry, pain_point, note</div>
                <div class="text-gray-600 text-xs mt-2">Max 500 prospects · email is mandatory for sending</div>
            </div>
        </label>
        <input type="file" accept=".csv,.txt" id="campaignCsvInput" class="hidden" onchange="handleCampaignCsvUpload(this)">
    </div>

    @if($csvFileName)
    <!-- Import Summary -->
    <div class="grid grid-cols-4 gap-3 mb-5">
        <div class="bg-green-900 border border-green-700 rounded-xl px-4 py-3 text-center">
            <div class="text-green-400 font-bold text-lg">{{ $importSummary['imported'] }}</div>
            <div class="text-green-600 text-xs mt-1">Imported</div>
        </div>
        <div class="bg-yellow-900 border border-yellow-700 rounded-xl px-4 py-3 text-center">
            <div class="text-yellow-400 font-bold text-lg">{{ $importSummary['duplicates'] }}</div>
            <div class="text-yellow-600 text-xs mt-1">Duplicates Skipped</div>
        </div>
        <div class="bg-red-900 border border-red-700 rounded-xl px-4 py-3 text-center">
            <div class="text-red-400 font-bold text-lg">{{ $importSummary['invalid_email'] }}</div>
            <div class="text-red-600 text-xs mt-1">Invalid/Missing Email</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-center">
            <div class="text-gray-300 font-bold text-lg">{{ $importSummary['already_active'] }}</div>
            <div class="text-gray-500 text-xs mt-1">Already in Campaign</div>
        </div>
    </div>
    @endif

    @if(!empty($prospects))
    <div class="bg-green-900 border border-green-700 rounded-xl px-5 py-3 mb-5">
        <div class="text-green-400 font-bold text-sm">✓ {{ count($prospects) }} prospects ready to import</div>
        @if($csvFileName)
        <div class="text-green-600 text-xs mt-1">{{ $csvFileName }}</div>
        @endif
    </div>

    <!-- Preview Table -->
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-700 flex justify-between">
            <span class="text-blue-400 text-xs font-bold tracking-widest">PREVIEW</span>
            <span class="text-gray-500 text-xs">Showing first 5 of {{ count($prospects) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700 bg-gray-750">
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">#</th>
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">Name</th>
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">Email</th>
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">Company</th>
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">Role</th>
                        <th class="text-left px-4 py-2 text-gray-500 text-xs">Industry</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($prospects, 0, 5) as $i => $p)
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition-colors">
                        <td class="px-4 py-2 text-gray-600">{{ $i+1 }}</td>
                        <td class="px-4 py-2 text-white font-medium">{{ $p['name'] }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $p['email'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ $p['company'] }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ $p['role'] }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ $p['industry'] }}</td>
                    </tr>
                    @endforeach
                    @if(count($prospects) > 5)
                    <tr>
                        <td colspan="6" class="px-4 py-2 text-center text-gray-600 text-xs">
                            ... and {{ count($prospects) - 5 }} more prospects
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

        <!-- ═══ STEP 3: EMAIL GENERATION SETTINGS ═══ -->
        @if($step === 3)
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-white mb-1">Email Generation</h2>
            <p class="text-gray-500 text-sm mb-6">Configure your offer and signature. AI will generate {{ count($prospects) }} personalized email sequences.</p>

            <div class="grid grid-cols-2 gap-5">
                <div class="col-span-2">
                    <label class="text-gray-400 text-xs mb-1 block">What you're selling *</label>
                    <input wire:model="offer" type="text" placeholder="AI-powered sales automation platform"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="text-gray-400 text-xs mb-1 block">Value Proposition *</label>
                    <input wire:model="value_prop" type="text" placeholder="We help companies close 3x more deals"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Call to Action</label>
                    <input wire:model="cta" type="text" placeholder="Book a 15-min call"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-gray-400 text-xs mb-1 block">Email Style</label>
                    <select wire:model="style"
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500">
                        <option value="direct">🎯 Direct & Bold</option>
                        <option value="friendly">😊 Friendly</option>
                        <option value="formal">💼 Formal</option>
                        <option value="witty">⚡ Witty</option>
                    </select>
                </div>

                <!-- Signature -->
                <div class="col-span-2 bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="text-blue-400 text-xs font-bold tracking-widest mb-3">✍️ EMAIL SIGNATURE</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">Your Name</label>
                            <input wire:model="sig_name" type="text" placeholder="Huzaifa Ahmad"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">Role</label>
                            <input wire:model="sig_role" type="text" placeholder="Business Development Executive"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">Company</label>
                            <input wire:model="sig_company" type="text" placeholder="RankSol"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs mb-1 block">Portfolio / Calendly</label>
                            <input wire:model="sig_link" type="text" placeholder="https://ranksol.com/"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generation Progress -->
            @if($generating)
            <div class="mt-5 bg-blue-900 border border-blue-700 rounded-xl p-4">
                <div class="flex justify-between mb-2">
                    <span class="text-blue-300 font-semibold text-sm">✨ Generating emails...</span>
                    <span class="text-blue-400 text-sm">{{ $genProgress }} / {{ count($prospects) }}</span>
                </div>
                <div class="bg-blue-800 rounded-full h-2">
                    <div class="h-full bg-gradient-to-r from-blue-400 to-purple-400 rounded-full transition-all"
                        style="width: {{ count($prospects) > 0 ? ($genProgress / count($prospects) * 100) : 0 }}%"></div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- ═══ STEP 4: REVIEW & EDIT ═══ -->
        @if($step === 4)
        <div>
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold text-white">Review & Edit Emails</h2>
                    <p class="text-gray-500 text-sm">{{ count($generatedEmails) }} sequences generated. Edit any email before launching.</p>
                </div>
                <div class="flex gap-3">
                    <span class="text-sm bg-green-900 text-green-400 px-3 py-1 rounded-full">
                        ✓ {{ collect($generatedEmails)->where('status', 'ready')->count() }} ready
                    </span>
                    @if(collect($generatedEmails)->where('status', 'failed')->count() > 0)
                    <span class="text-sm bg-red-900 text-red-400 px-3 py-1 rounded-full">
                        ✗ {{ collect($generatedEmails)->where('status', 'failed')->count() }} failed
                    </span>
                    @endif
                </div>
            </div>

            @foreach($generatedEmails as $index => $gen)
            @if($gen['status'] === 'ready')
            <div class="bg-gray-900 border border-gray-800 rounded-2xl mb-4 overflow-hidden">
                <!-- Prospect Header -->
                <div class="bg-gray-800 px-5 py-3 flex justify-between items-center border-b border-gray-700">
                    <div>
                        <span class="text-white font-bold">{{ $gen['prospect']['name'] }}</span>
                        <span class="text-gray-500 text-sm ml-2">{{ $gen['prospect']['company'] }}</span>
                        <span class="text-gray-600 text-xs ml-2">{{ $gen['prospect']['role'] }}</span>
                    </div>
                    <span class="text-xs px-2 py-1 bg-green-900 text-green-400 rounded-full">✓ Ready</span>
                </div>

                <!-- Subject Lines -->
                <div class="px-5 py-3 border-b border-gray-800 bg-gray-900">
                    <div class="text-purple-400 text-xs font-bold mb-1">SUBJECTS</div>
                    <div class="text-gray-300 text-sm">A: {{ $gen['subject1'] }}</div>
                    <div class="text-gray-500 text-sm">B: {{ $gen['subject2'] }}</div>
                </div>

                <!-- Email Cards -->
                @foreach([1 => 'EMAIL 1 — OPENER', 2 => 'FOLLOW-UP (Day '.$followup_delay_1.')', 3 => 'FINAL (Day '.$followup_delay_2.')'] as $num => $label)
                <div class="border-b border-gray-800 last:border-0">
                    <div class="flex justify-between items-center px-5 py-2 bg-gray-850">
                        <span class="text-blue-400 text-xs font-bold">{{ $label }}</span>
                        @if($editingIndex === $index && $editEmailNum === $num)
                        <div class="flex gap-2">
                            <button wire:click="saveEmailEdit"
                                class="text-xs px-3 py-1 bg-green-800 hover:bg-green-700 text-green-300 rounded-lg">💾 Save</button>
                            <button wire:click="cancelEmailEdit"
                                class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg">✕ Cancel</button>
                        </div>
                        @else
                        <button wire:click="startEditEmail({{ $index }}, {{ $num }})"
                            class="text-xs px-3 py-1 bg-indigo-900 hover:bg-indigo-800 text-indigo-300 rounded-lg">✏️ Edit</button>
                        @endif
                    </div>
                    <div class="px-5 py-3">
                        @if($editingIndex === $index && $editEmailNum === $num)
                        <textarea wire:model.live="editBuffer" rows="8"
                            class="w-full bg-gray-800 border border-indigo-700 rounded-xl px-4 py-3 text-gray-200 text-sm font-sans focus:outline-none resize-y"></textarea>
                        @else
                        <div class="text-gray-300 text-sm leading-relaxed space-y-2 font-sans">
                            @foreach(explode("\n\n", $gen["email{$num}"] ?? '') as $para)
                                @if(trim($para))<p>{!! nl2br(e(trim($para))) !!}</p>@endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            @endforeach
        </div>
        @endif

        <!-- ═══ STEP 5: SUCCESS ═══ -->
        @if($step === 5)
        <div class="text-center py-16">
            <div class="text-7xl mb-6">🚀</div>
            <h2 class="text-3xl font-bold text-white mb-3">Campaign Launched!</h2>
            <p class="text-gray-400 mb-2">Your campaign <span class="text-blue-400 font-semibold">{{ $name }}</span> is now active.</p>
            <p class="text-gray-500 text-sm mb-8">{{ count($generatedEmails) }} prospects added · Emails scheduled automatically</p>

            <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto mb-8">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-400">{{ count($generatedEmails) }}</div>
                    <div class="text-gray-500 text-xs mt-1">Prospects</div>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-400">{{ $daily_limit }}</div>
                    <div class="text-gray-500 text-xs mt-1">Emails/Day</div>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-400">{{ $followup_delay_2 }}</div>
                    <div class="text-gray-500 text-xs mt-1">Day Sequence</div>
                </div>
            </div>

            <div class="flex gap-4 justify-center">
                <a href="{{ route('campaigns') }}"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-xl hover:from-blue-500 hover:to-purple-500 transition-all">
                    View Campaign Dashboard →
                </a>
                <a href="{{ route('campaign.create') }}"
                    class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold rounded-xl transition-all">
                    Create Another Campaign
                </a>
            </div>
        </div>
        @endif

        <!-- NAVIGATION BUTTONS -->
        @if($step < 5)
        <div class="flex justify-between mt-6">
            <button wire:click="prevStep" @if($step === 1) disabled @endif
                class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold rounded-xl transition-all disabled:opacity-30">
                ← Back
            </button>

            @if($step === 4)
            <button wire:click="launchCampaign" wire:loading.attr="disabled"
                class="px-8 py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-500 hover:to-blue-500 text-white font-bold rounded-xl transition-all">
                <span wire:loading.remove>🚀 Launch Campaign</span>
                <span wire:loading>Launching...</span>
            </button>
            @else
            <button wire:click="nextStep" wire:loading.attr="disabled"
                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all">
                <span wire:loading.remove wire:target="nextStep">
                    {{ $step === 3 ? '✨ Generate Emails' : 'Next →' }}
                </span>
                <span wire:loading wire:target="nextStep">Processing...</span>
            </button>
            @endif
        </div>
        @endif
    </div>
</div>

<script>
function handleCampaignCsvUpload(input) {
    const file = input.files[0];
    if (!file) return;

    const nameEl   = document.getElementById('csv-filename');
    const dropzone = document.getElementById('campaignDropzone');

    if (nameEl) nameEl.textContent = '⏳ Loading ' + file.name + '...';
    if (dropzone) {
        dropzone.classList.remove('border-gray-700');
        dropzone.classList.add('border-yellow-500');
    }

    const reader = new FileReader();
    reader.onload = function(ev) {
        @this.call('loadCsv', ev.target.result, file.name);
    };
    reader.onerror = function() {
        if (nameEl) nameEl.textContent = '✗ Error reading file';
    };
    reader.readAsText(file);
}

function openGmail(subject, body) {
    window.open('https://mail.google.com/mail/?view=cm&fs=1&su=' +
        encodeURIComponent(subject) + '&body=' + encodeURIComponent(body), '_blank');
}

function openOutlook(subject, body) {
    window.open('https://outlook.live.com/mail/0/deeplink/compose?subject=' +
        encodeURIComponent(subject) + '&body=' + encodeURIComponent(body), '_blank');
}
</script>