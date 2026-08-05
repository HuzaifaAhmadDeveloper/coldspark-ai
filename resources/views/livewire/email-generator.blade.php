<div class="min-h-screen bg-gray-950 text-white">
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
    <a href="{{ route('dashboard') }}"
        class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-900 text-blue-300' : 'text-gray-400 hover:text-white' }}">
        ✉ Generator
    </a>
    <a href="{{ route('bulk') }}"
        class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('bulk') ? 'bg-purple-900 text-purple-300' : 'text-gray-400 hover:text-white' }}">
        📂 Bulk CSV
    </a>
    <a href="{{ route('campaigns') }}"
    class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('campaigns') ? 'bg-green-900 text-green-300' : 'text-gray-400 hover:text-white' }}">
    📣 Campaigns
</a>
    <a href="{{ route('history') }}"
        class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('history') ? 'bg-green-900 text-green-300' : 'text-gray-400 hover:text-white' }}">
        🕐 History
    </a>
    <a href="{{ route('warmup') }}"
    class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('warmup') ? 'bg-orange-900 text-orange-300' : 'text-gray-400 hover:text-white' }}">
    🔥 Warmup
</a>
    <a href="{{ route('team') }}"
    class="text-sm px-3 py-1 rounded-lg {{ request()->routeIs('team') ? 'bg-indigo-900 text-indigo-300' : 'text-gray-400 hover:text-white' }}">
    👥 Team
</a>
    <a href="{{ route('billing.plans') }}"
        class="text-sm px-3 py-1 rounded-lg bg-yellow-900 text-yellow-400 hover:bg-yellow-800 transition-all font-semibold">
        ⚡ Upgrade
    </a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="text-sm px-3 py-1 rounded-lg bg-red-900 text-red-400 hover:bg-red-800 transition-all">
            Logout
        </button>
    </form>
</div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- LEFT: FORM -->
        <div class="space-y-6">

            <!-- PROSPECT DETAILS -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">PROSPECT DETAILS</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Full Name *</label>
                        <input wire:model="name" type="text" placeholder="Sarah Johnson"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        @error('name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Company *</label>
                        <input wire:model="company" type="text" placeholder="TechFlow Inc"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        @error('company') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Role / Title *</label>
                        <input wire:model="role" type="text" placeholder="Head of Marketing"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        @error('role') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Industry</label>
                        <input wire:model="industry" type="text" placeholder="SaaS / E-commerce"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="text-gray-500 text-xs mb-1 block">Pain Point / Goal</label>
                        <input wire:model="pain_point" type="text" placeholder="Struggling to scale outbound sales"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="text-gray-500 text-xs mb-1 block">Personal Note (optional)</label>
                        <input wire:model="personal_note" type="text" placeholder="Saw their recent funding announcement"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- YOUR OFFER -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">YOUR OFFER</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">What you're selling *</label>
                        <input wire:model="offer" type="text" placeholder="AI-powered sales automation platform"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        @error('offer') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Value Proposition *</label>
                        <input wire:model="value_prop" type="text" placeholder="We help SaaS companies close 3x more deals"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        @error('value_prop') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">Call to Action</label>
                        <input wire:model="cta" type="text" placeholder="Book a 15-min call"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- STYLE -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">EMAIL STYLE</h2>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['direct' => ['🎯','Direct & Bold','Problem → Solution → CTA'], 'friendly' => ['😊','Friendly','Warm conversational tone'], 'formal' => ['💼','Formal','Professional enterprise pitch'], 'witty' => ['⚡','Witty','Clever hook, light humor']] as $key => $s)
                    <div wire:click="$set('style', '{{ $key }}')"
                        class="p-3 rounded-xl cursor-pointer border transition-all {{ $style === $key ? 'bg-blue-900 border-blue-500' : 'bg-gray-800 border-gray-700 hover:border-gray-500' }}">
                        <div class="text-xl mb-1">{{ $s[0] }}</div>
                        <div class="font-bold text-sm {{ $style === $key ? 'text-blue-300' : 'text-gray-300' }}">{{ $s[1] }}</div>
                        <div class="text-xs text-gray-500">{{ $s[2] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- ERROR -->
            @if($error)
            <div class="bg-red-900 border border-red-700 rounded-xl p-4 text-red-300 text-sm">{{ $error }}</div>
            @endif
<!-- SIGNATURE -->
<div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
    <h2 class="text-blue-400 font-bold text-xs tracking-widest mb-4">✍️ EMAIL SIGNATURE</h2>
    <p class="text-gray-500 text-xs mb-4">Added at the end of every email for a professional touch.</p>
    <div class="grid grid-cols-2 gap-4">
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
            <input wire:model="sig_company" type="text" placeholder="Nimble Web Solutions"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-gray-500 text-xs mb-1 block">Portfolio / Calendly Link</label>
            <input wire:model="sig_link" type="text" placeholder="https://calendly.com/huzaifa"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
        </div>
    </div>

    <!-- PREVIEW -->
    @if($sig_name)
    <div class="mt-4 bg-gray-800 rounded-xl p-3 border border-gray-700">
        <div class="text-gray-500 text-xs mb-2">SIGNATURE PREVIEW</div>
        <div class="text-gray-300 text-sm font-mono whitespace-pre-line">Best regards,
{{ $sig_name }}{{ $sig_role ? "\n".$sig_role : '' }}{{ $sig_company ? "\n".$sig_company : '' }}{{ $sig_link ? "\n".$sig_link : '' }}</div>
    </div>
    @endif
</div>
            <!-- GENERATE BUTTON -->
            <button wire:click="generate" wire:loading.attr="disabled"
                class="w-full py-4 rounded-xl font-bold text-lg bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 transition-all disabled:opacity-50">
                <span wire:loading.remove>✨ Generate Email Sequence</span>
                <span wire:loading>⏳ Writing your emails...</span>
            </button>
        </div>

        <!-- RIGHT: RESULTS -->
        <div>
            @if(empty($result) && !$loading)
            <div class="h-full flex flex-col items-center justify-center text-gray-600 text-center py-20">
                <div class="text-6xl mb-4">✉️</div>
                <div class="text-lg font-semibold mb-2">Your emails will appear here</div>
                <div class="text-sm">Fill in the details and click Generate</div>
            </div>
            @endif

            @if(!empty($result))
            <!-- SUBJECT A/B -->
            <div class="bg-gray-900 border border-purple-800 rounded-2xl p-5 mb-4">
                <div class="text-purple-400 font-bold text-xs tracking-widest mb-3">🧪 SUBJECT LINE A/B TEST</div>
                @foreach(['subject1' => 'A', 'subject2' => 'B'] as $key => $label)
                <div wire:click="$set('selectedSubject', {{ $loop->index }})"
                    class="flex items-center gap-3 p-3 rounded-xl cursor-pointer mb-2 transition-all {{ $selectedSubject === $loop->index ? 'bg-purple-900 border border-purple-500' : 'bg-gray-800 border border-gray-700' }}">
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs {{ $selectedSubject === $loop->index ? 'border-purple-400 bg-purple-600 text-white' : 'border-gray-600' }}">
                        {{ $selectedSubject === $loop->index ? '✓' : '' }}
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Option {{ $label }}</div>
                        <div class="text-white text-sm">{{ $result[$key] ?? '' }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- EMAIL CARDS -->
           @foreach(['email1' => ['EMAIL 1 — OPENER', 'Send now', 1], 'email2' => ['EMAIL 2 — FOLLOW-UP', 'Send day 3', 2], 'email3' => ['EMAIL 3 — FINAL', 'Send day 7', 3]] as $key => [$label, $timing, $num])
@php
    $isEditing  = ${'editingEmail'.$num} ?? false;
    $hasUnsaved = ${'hasUnsaved'.$num} ?? false;
    $isEdited   = !empty($this->result['edited_email'.$num]);
@endphp
<div class="bg-gray-900 border border-gray-800 rounded-2xl mb-4 overflow-hidden" data-unsaved="{{ $hasUnsaved ? 'true' : 'false' }}">
    <!-- CARD HEADER -->
    <div class="flex items-center justify-between px-5 py-3 bg-gray-800 border-b border-gray-700">
        <div class="flex items-center gap-2">
            <span class="text-blue-400 font-bold text-xs tracking-widest">{{ $label }}</span>
            <span class="text-gray-600 text-xs">{{ $timing }}</span>
            @if($isEdited)
            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-900 text-indigo-300 font-semibold">✏️ Manually Edited</span>
            @else
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400 font-medium">🤖 AI Generated</span>
            @endif
            @if($hasUnsaved)
            <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-900 text-yellow-400 font-semibold">● Unsaved</span>
            @endif
        </div>
        <div class="flex gap-2 items-center">
            @if(!$isEditing)
                <!-- Normal mode buttons -->
                <button wire:click="startEdit({{ $num }})"
                    class="text-xs px-3 py-1 rounded-lg bg-indigo-900 hover:bg-indigo-800 text-indigo-300 transition-all font-semibold">
                    ✏️ Edit
                </button>
                <button wire:click="regenerateEmail({{ $num }})"
                    class="text-xs px-3 py-1 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 transition-all">
                    🔄 Redo
                </button>
                <button
                    data-subject="{{ $selectedSubject === 0 ? ($result['subject1'] ?? '') : ($result['subject2'] ?? '') }}"
                    data-body="{{ $result[$key] ?? '' }}"
                    onclick="copyEmail(this.dataset.body)"
                    class="text-xs px-3 py-1 rounded-lg bg-blue-900 hover:bg-blue-800 text-blue-300 transition-all">
                    📋 Copy
                </button>
                <button
                    data-subject="{{ $selectedSubject === 0 ? ($result['subject1'] ?? '') : ($result['subject2'] ?? '') }}"
                    data-body="{{ $result[$key] ?? '' }}"
                    onclick="openGmail(this.dataset.subject, this.dataset.body)"
                    class="text-xs px-3 py-1 rounded-lg bg-red-900 hover:bg-red-800 text-red-300 transition-all">
                    📧 Gmail
                </button>
                <button
                    data-subject="{{ $selectedSubject === 0 ? ($result['subject1'] ?? '') : ($result['subject2'] ?? '') }}"
                    data-body="{{ $result[$key] ?? '' }}"
                    onclick="openOutlook(this.dataset.subject, this.dataset.body)"
                    class="text-xs px-3 py-1 rounded-lg bg-blue-900 hover:bg-blue-700 text-blue-200 transition-all">
                    📨 Outlook
                </button>
            @else
                <!-- Edit mode buttons -->
                <span class="text-xs text-gray-500" id="char-count-{{ $num }}">0 chars</span>
                <button wire:click="saveEdit({{ $num }})"
                    class="text-xs px-3 py-1 rounded-lg bg-green-800 hover:bg-green-700 text-green-300 transition-all font-semibold">
                    💾 Save
                </button>
                <button wire:click="cancelEdit({{ $num }})"
                    class="text-xs px-3 py-1 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 transition-all">
                    ✕ Cancel
                </button>
                <button wire:click="resetToAI({{ $num }})"
                    class="text-xs px-3 py-1 rounded-lg bg-orange-900 hover:bg-orange-800 text-orange-300 transition-all">
                    🤖 Reset AI
                </button>
            @endif
        </div>
    </div>

    <!-- CARD BODY -->
    @if(!$isEditing)
    <!-- Display Mode -->
    <div class="p-5">
        <div class="text-gray-300 text-sm leading-relaxed font-sans space-y-3" id="email-body-{{ $key }}">
            @foreach(explode("\n\n", $result[$key] ?? '') as $paragraph)
                @if(trim($paragraph))
                    <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                @endif
            @endforeach
        </div>
    </div>
    @else
    <!-- Edit Mode -->
    <div class="p-5">
        <textarea
            wire:model.live="editBuffer{{ $num }}"
            id="editor-{{ $num }}"
            rows="12"
            onkeyup="updateCharCount({{ $num }}); @this.call('markUnsaved', {{ $num }})"
            class="w-full bg-gray-800 border border-indigo-700 rounded-xl px-4 py-3 text-gray-200 text-sm leading-relaxed font-sans focus:outline-none focus:border-indigo-500 resize-y"
            placeholder="Edit your email here...">{{ $result[$key] ?? '' }}</textarea>
        <div class="flex justify-between items-center mt-2">
            <span class="text-gray-600 text-xs">Tip: Use blank lines between paragraphs for proper formatting</span>
            <span class="text-gray-500 text-xs" id="char-count-bottom-{{ $num }}"></span>
        </div>
    </div>
    @endif
</div>
@endforeach

            <!-- SAVE BUTTON -->
            @if(!$saved)
            <button wire:click="saveSequence"
                class="w-full py-3 rounded-xl font-bold bg-green-800 hover:bg-green-700 text-green-300 transition-all">
                💾 Save to History
            </button>
            @else
            <div class="w-full py-3 rounded-xl font-bold bg-gray-800 text-green-400 text-center">
                ✅ Saved to History!
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

<script>
function copyEmail(body) {
    navigator.clipboard.writeText(body).then(() => {
        alert('Copied to clipboard!');
    });
}

function openGmail(subject, body) {
    const url = 'https://mail.google.com/mail/?view=cm&fs=1&su=' +
        encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    window.open(url, '_blank');
}

function openOutlook(subject, body) {
    const url = 'https://outlook.live.com/mail/0/deeplink/compose?subject=' +
        encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    window.open(url, '_blank');
}

function updateCharCount(num) {
    const editor = document.getElementById('editor-' + num);
    if (!editor) return;
    const count = editor.value.length;
    const el = document.getElementById('char-count-' + num);
    const el2 = document.getElementById('char-count-bottom-' + num);
    const text = count + ' characters';
    if (el) el.textContent = text;
    if (el2) el2.textContent = text;
}

// Unsaved changes warning on page leave
window.addEventListener('beforeunload', function(e) {
    const unsaved = document.querySelector('[data-unsaved="true"]');
    if (unsaved) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes!';
    }
});
</script>