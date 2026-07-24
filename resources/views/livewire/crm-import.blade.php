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
            <a href="{{ route('crm') }}" class="text-sm px-3 py-1 rounded-lg bg-indigo-900 text-indigo-300">🔗 CRM Import</a>
            <a href="{{ route('history') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🕐 History</a>
            <a href="{{ route('team') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">👥 Team</a>
            <a href="{{ route('warmup') }}" class="text-sm px-3 py-1 rounded-lg text-gray-400 hover:text-white">🔥 Warmup</a>
            <a href="{{ route('billing.plans') }}" class="text-sm px-3 py-1 rounded-lg bg-yellow-900 text-yellow-400 font-semibold">⚡ Upgrade</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800">Logout</button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">🔗 CRM Import</h1>
            <p class="text-gray-500">Import contacts from HubSpot, Salesforce, Pipedrive, Zoho or any CSV — directly into your prospect list.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: SETTINGS -->
            <div class="space-y-5">

                <!-- CRM TYPE -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">SELECT YOUR CRM</h2>
                    <div class="space-y-2">
                        @foreach([
                            'hubspot'     => ['🟠', 'HubSpot',     'Export from Contacts → Actions → Export'],
                            'salesforce'  => ['🔵', 'Salesforce',  'Export from Contacts → List View → Export'],
                            'pipedrive'   => ['🟢', 'Pipedrive',   'Export from Contacts → People → Export'],
                            'zoho'        => ['🔴', 'Zoho CRM',    'Export from Contacts → Export Contacts'],
                            'csv'         => ['⚪', 'Generic CSV', 'Any CSV with name, company, role columns'],
                        ] as $key => [$icon, $label, $hint])
                        <div wire:click="$set('crmType', '{{ $key }}')"
                            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer border transition-all {{ $crmType === $key ? 'bg-blue-900 border-blue-500' : 'bg-gray-800 border-gray-700 hover:border-gray-500' }}">
                            <span class="text-xl">{{ $icon }}</span>
                            <div>
                                <div class="font-semibold text-sm {{ $crmType === $key ? 'text-blue-300' : 'text-gray-300' }}">{{ $label }}</div>
                                <div class="text-xs text-gray-500">{{ $hint }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- HOW TO EXPORT -->
                <div class="bg-gray-900 border border-yellow-900 rounded-2xl p-5">
                    <h2 class="text-yellow-400 font-bold text-xs tracking-widest mb-3">💡 HOW TO EXPORT</h2>
                    @php
                        $guides = [
                            'hubspot'    => ['Go to CRM → Contacts', 'Click Actions → Export', 'Select CSV format', 'Download and upload here'],
                            'salesforce' => ['Go to Contacts tab', 'Click list view → Export', 'Choose CSV / Excel', 'Upload exported file here'],
                            'pipedrive'  => ['Go to Contacts → People', 'Click ··· → Export data', 'Select CSV format', 'Upload here'],
                            'zoho'       => ['Go to Contacts module', 'Click Actions → Export', 'Choose CSV', 'Upload exported file here'],
                            'csv'        => ['Prepare CSV file', 'Required: name, company', 'Optional: role, industry', 'Upload below'],
                        ];
                    @endphp
                    <ol class="space-y-2">
                        @foreach($guides[$crmType] as $i => $step)
                        <li class="flex items-start gap-2 text-sm text-gray-400">
                            <span class="text-yellow-500 font-bold">{{ $i+1 }}.</span>
                            {{ $step }}
                        </li>
                        @endforeach
                    </ol>
                </div>

                <!-- UPLOAD -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">UPLOAD FILE</h2>
                    <label class="block cursor-pointer">
                        <div class="border-2 border-dashed border-gray-700 rounded-xl p-6 text-center hover:border-blue-500 transition-all">
                            <div class="text-3xl mb-2">📁</div>
                            <div class="text-sm text-gray-400" id="crm-dropzone-text">Click to upload CSV</div>
                            <div class="text-xs text-gray-600 mt-1">Max 100 contacts, 5MB</div>
                        </div>
                        <input type="file" accept=".csv,.txt" class="hidden" id="crmFileInput">
                    </label>
                    @error('file') <p class="text-red-400 text-xs mt-2">{{ $message }}</p> @enderror

                    @if($total > 0)
                    <div class="mt-3 bg-green-900 border border-green-700 rounded-xl px-4 py-2 text-green-400 text-sm text-center">
                        ✓ {{ $total }} contacts detected
                    </div>
                    @endif
                </div>
                @if($error)
                <div class="bg-red-900 border border-red-700 rounded-xl p-4 text-red-300 text-sm">{{ $error }}</div>
                @endif

                @if($success)
                <div class="bg-green-900 border border-green-700 rounded-xl p-4 text-green-300 text-sm">{{ $success }}</div>
                @endif

                <!-- SIGNATURE -->
<div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-3">✍️ EMAIL SIGNATURE</h2>
    <p class="text-gray-500 text-xs mb-3">Added to emails when generating sequences for imported contacts.</p>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-gray-500 text-xs mb-1 block">Your Name</label>
            <input wire:model="sig_name" type="text" placeholder="Huzaifa Ahmad"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-gray-500 text-xs mb-1 block">Your Role</label>
            <input wire:model="sig_role" type="text" placeholder="Business Development Executive"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-gray-500 text-xs mb-1 block">Company</label>
            <input wire:model="sig_company" type="text" placeholder="RankSol"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-gray-500 text-xs mb-1 block">Portfolio / Calendly</label>
            <input wire:model="sig_link" type="text" placeholder="https://ranksol.com/"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
    </div>
    @if($sig_name)
    <div class="mt-3 bg-gray-800 rounded-xl p-3 border border-gray-700">
        <div class="text-gray-500 text-xs mb-1">PREVIEW</div>
        <div class="text-gray-300 text-xs font-mono whitespace-pre-line">Best regards,
{{ $sig_name }}{{ $sig_role ? "\n".$sig_role : '' }}{{ $sig_company ? "\n".$sig_company : '' }}{{ $sig_link ? "\n".$sig_link : '' }}</div>
    </div>
    @endif
</div>

                <button wire:click="importContacts" wire:loading.attr="disabled"
                    @if($total === 0 || $processing) disabled @endif
                    class="w-full py-4 rounded-xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 transition-all disabled:opacity-40">
                    <span wire:loading.remove wire:target="importContacts">
                        🔗 Import {{ $total > 0 ? $total : '' }} Contacts
                    </span>
                    <span wire:loading wire:target="importContacts">⏳ Importing...</span>
                </button>

                <!-- AFTER IMPORT -->
                @if($done)
                <a href="{{ route('bulk') }}"
                    class="block text-center w-full py-3 rounded-xl font-bold bg-purple-800 hover:bg-purple-700 text-purple-300 transition-all">
                    ✨ Generate Emails for Imported Contacts →
                </a>
                @endif
            </div>

            <!-- RIGHT: PREVIEW + HISTORY -->
            <div class="lg:col-span-2 space-y-5">

                <!-- PREVIEW TABLE -->
                @if(!empty($preview) && !$done)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-800 flex justify-between">
                        <span class="text-blue-400 font-bold text-xs tracking-widest">CONTACT PREVIEW</span>
                        <span class="text-gray-500 text-xs">Showing {{ min(10, count($preview)) }} of {{ $total }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-800">
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Name</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Company</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Role</th>
                                    <th class="text-left px-4 py-2 text-gray-500 text-xs">Industry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($preview, 0, 10) as $contact)
                                <tr class="border-b border-gray-800 hover:bg-gray-800 transition-colors">
                                    <td class="px-4 py-2 text-white">{{ $contact['name'] ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $contact['company'] ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $contact['role'] ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $contact['industry'] ?: '—' }}</td>
                                </tr>
                                @endforeach
                                @if($total > 10)
                                <tr><td colspan="4" class="px-4 py-2 text-center text-gray-600 text-xs">... and {{ $total - 10 }} more contacts</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- SUCCESS RESULT -->
                @if($done)
                <div class="bg-gray-900 border border-green-800 rounded-2xl p-6">
                    <div class="text-center mb-6">
                        <div class="text-5xl mb-3">🎉</div>
                        <h2 class="text-xl font-bold text-white mb-1">Import Complete!</h2>
                        <p class="text-gray-400 text-sm">Contacts have been added to your prospect list</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-green-900 border border-green-700 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-green-400">{{ $importedCount }}</div>
                            <div class="text-green-600 text-sm mt-1">Imported</div>
                        </div>
                        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-gray-400">{{ $skippedCount }}</div>
                            <div class="text-gray-600 text-sm mt-1">Skipped (duplicates)</div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- EMPTY STATE -->
                @if(empty($preview) && !$done)
                <div class="flex flex-col items-center justify-center h-64 text-gray-600 bg-gray-900 border border-gray-800 rounded-2xl">
                    <div class="text-5xl mb-4">🔗</div>
                    <div class="text-lg font-semibold mb-2">Select your CRM & upload file</div>
                    <div class="text-sm text-center px-8">Export contacts from your CRM as CSV and upload here to import them as prospects</div>
                </div>
                @endif

                <!-- IMPORT HISTORY -->
                @if(!empty($history))
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-800">
                        <span class="text-blue-400 font-bold text-xs tracking-widest">IMPORT HISTORY</span>
                    </div>
                    <div class="divide-y divide-gray-800">
                        @foreach($history as $h)
                        <div class="px-5 py-4 flex justify-between items-center">
                            <div>
                                <div class="text-white text-sm font-semibold">{{ ucfirst($h['crm_type']) }} — {{ $h['filename'] }}</div>
                                <div class="text-gray-500 text-xs mt-1">
                                    {{ $h['imported'] }} imported · {{ $h['skipped'] }} skipped
                                </div>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full {{ $h['status'] === 'completed' ? 'bg-green-900 text-green-400' : 'bg-yellow-900 text-yellow-400' }}">
                                {{ ucfirst($h['status']) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('crmFileInput');
            if (input) {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    document.getElementById('crm-dropzone-text').textContent = file.name;
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        @this.call('loadCsvData', ev.target.result, file.name);
                    };
                    reader.readAsText(file);
                });
            }
        });
        </script>
</div>