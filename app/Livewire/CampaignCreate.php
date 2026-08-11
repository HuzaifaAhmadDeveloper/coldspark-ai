<?php
namespace App\Livewire;
use App\Models\Campaign;
use App\Models\Prospect;
use App\Services\CampaignService;
use App\Services\GroqService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CampaignCreate extends Component
{
    // Step tracking
    public int    $step         = 1;
    public int    $totalSteps   = 5;

    // Step 1 - Campaign Settings
    public string $name                = '';
    public int    $daily_limit         = 30;
    public int    $gap_minutes         = 10;
    public string $start_date          = '';
    public string $working_hours_start = '09:00';
    public string $working_hours_end   = '17:00';
    public string $timezone            = 'Asia/Karachi';
    public int    $followup_delay_1    = 3;
    public int    $followup_delay_2    = 7;
    public bool   $stop_on_reply       = true;
    public string $notes               = '';

    // Step 2 - Prospects
    public array  $prospects    = [];
    public string $csvData      = '';
    public string $csvFileName  = '';
    public string $prospectSource = 'csv'; // csv or manual

    // Import summary (populated by loadCsv)
    public array $importSummary = [
        'imported'       => 0,
        'duplicates'     => 0,
        'invalid_email'  => 0,
        'already_active' => 0,
        'suppressed'     => 0,
    ];

    // Step 3 - Email Generation
    public string $offer        = '';
    public string $value_prop   = '';
    public string $cta          = 'Book a 15-min call';
    public string $style        = 'direct';
    public string $sig_name     = '';
    public string $sig_role     = '';
    public string $sig_company  = '';
    public string $sig_link     = '';
    public array  $generatedEmails = [];
    public bool   $generating   = false;
    public int    $genProgress  = 0;

    // Step 4 - Review
    public int    $editingIndex = -1;
    public string $editBuffer   = '';
    public int    $editEmailNum = 1;

    // Error & Success
    public string $error        = '';
    public ?int   $campaignId   = null;

    public function loadCsv(string $csvContent, string $fileName = ''): void
    {
        $this->prospects     = [];
        $this->csvFileName   = $fileName;
        $this->error         = '';
        $this->importSummary = [
            'imported'       => 0,
            'duplicates'     => 0,
            'invalid_email'  => 0,
            'already_active' => 0,
            'suppressed'     => 0,
        ];

        if (empty(trim($csvContent))) {
            $this->error = 'File is empty.';
            return;
        }

        try {
            // Strip UTF-8 BOM if present
            $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

            $lines = explode("\n", trim($csvContent));
            $lines = array_map(fn($l) => trim(str_replace("\r", '', $l)), $lines);
            $lines = array_filter($lines);
            $lines = array_values($lines);

            if (count($lines) < 2) {
                $this->error = 'CSV must have at least 2 rows (header + data).';
                return;
            }

            $rawHeaders = str_getcsv($lines[0]);
            $headers = array_map(function($h) {
                $clean = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $h);
                return strtolower(trim($clean));
            }, $rawHeaders);

            // Emails already being actively worked by another campaign for this user —
            // re-importing them would double-email the same prospect.
            $activeEmails = Prospect::where('user_id', Auth::id())
                ->whereHas('campaignEmails', fn($q) => $q->whereIn('status', ['scheduled', 'sending']))
                ->pluck('email')
                ->filter()
                ->map(fn($e) => strtolower(trim($e)))
                ->flip()
                ->all();

            // Unsubscribed / hard-bounced / complained / manually suppressed — across
            // every campaign this user has ever run, not just the active one.
            $suppressedEmails = \App\Models\Suppression::where('user_id', Auth::id())
                ->pluck('email')
                ->flip()
                ->all();

            $seenEmails = [];

            foreach (array_slice($lines, 1) as $line) {
                $row = str_getcsv($line);
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;

                $rowPadded = $row;
                while (count($rowPadded) < count($headers)) {
                    $rowPadded[] = '';
                }

                $data = array_combine($headers, array_slice($rowPadded, 0, count($headers)));

                $name     = trim($data['name'] ?? $data['full_name'] ?? $data['prospect_name'] ?? $data['contact'] ?? $row[0] ?? '');
                $company  = trim($data['company'] ?? $data['company_name'] ?? $data['organization'] ?? $row[1] ?? '');
                $role     = trim($data['role'] ?? $data['title'] ?? $data['position'] ?? $data['job_title'] ?? $row[2] ?? '');
                $industry = trim($data['industry'] ?? $data['sector'] ?? $row[3] ?? '');
                $pain     = trim($data['pain_point'] ?? $data['pain'] ?? $data['painpoint'] ?? $data['challenge'] ?? $row[4] ?? '');
                $note     = trim($data['note'] ?? $data['notes'] ?? $data['personal_note'] ?? $row[5] ?? '');
                $email    = trim($data['email'] ?? $data['email_address'] ?? $data['mail'] ?? '');

                if ($name === '' && $company === '') continue;

                // Email is mandatory — campaign emails can only be sent to a real address.
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->importSummary['invalid_email']++;
                    continue;
                }

                $emailKey = strtolower($email);

                if (isset($seenEmails[$emailKey])) {
                    $this->importSummary['duplicates']++;
                    continue;
                }
                $seenEmails[$emailKey] = true;

                if (isset($suppressedEmails[$emailKey])) {
                    $this->importSummary['suppressed']++;
                    continue;
                }

                if (isset($activeEmails[$emailKey])) {
                    $this->importSummary['already_active']++;
                    continue;
                }

                $this->prospects[] = [
                    'name'          => $name,
                    'email'         => $email,
                    'company'       => $company,
                    'role'          => $role,
                    'industry'      => $industry,
                    'pain_point'    => $pain,
                    'personal_note' => $note,
                ];
                $this->importSummary['imported']++;

                if (count($this->prospects) >= 500) break;
            }

            if (empty($this->prospects)) {
                $this->error = 'No valid prospect rows found in CSV. '
                    . 'Every row needs a valid email address, and duplicates/already-active prospects are skipped.';
            }

            Log::info("Campaign CSV imported by user #" . Auth::id() . ": {$fileName}", $this->importSummary);

        } catch (\Exception $e) {
            $this->error = 'Could not parse CSV: ' . $e->getMessage();
        }
    }

    public function generateEmails(): void
    {
        if (empty($this->prospects)) {
            $this->error = 'Please add prospects first.';
            return;
        }
        if (empty($this->offer) || empty($this->value_prop)) {
            $this->error = 'Please fill offer and value proposition.';
            return;
        }

        $this->generating     = true;
        $this->genProgress    = 0;
        $this->generatedEmails = [];
        $this->error          = '';

        $groq = new GroqService();

        foreach ($this->prospects as $i => $prospect) {
            try {
                $result = $groq->generateSequence([
                    'name'          => $prospect['name'],
                    'company'       => $prospect['company'],
                    'role'          => $prospect['role'],
                    'industry'      => $prospect['industry'],
                    'pain_point'    => $prospect['pain_point'],
                    'personal_note' => $prospect['personal_note'],
                    'offer'         => $this->offer,
                    'value_prop'    => $this->value_prop,
                    'cta'           => $this->cta,
                    'style'         => $this->style,
                    'sig_name'      => $this->sig_name,
                    'sig_role'      => $this->sig_role,
                    'sig_company'   => $this->sig_company,
                    'sig_link'      => $this->sig_link,
                ]);

                $this->generatedEmails[] = [
                    'prospect'  => $prospect,
                    'subject1'  => $result['subject1'] ?? '',
                    'subject2'  => $result['subject2'] ?? '',
                    'email1'    => $result['email1']   ?? '',
                    'email2'    => $result['email2']   ?? '',
                    'email3'    => $result['email3']   ?? '',
                    'status'    => 'ready',
                ];
            } catch (\Exception $e) {
                $this->generatedEmails[] = [
                    'prospect' => $prospect,
                    'status'   => 'failed',
                    'error'    => $e->getMessage(),
                ];
            }
            $this->genProgress = $i + 1;
        }

        $this->generating = false;
        $this->step = 4; // Go to review step

        $ready  = collect($this->generatedEmails)->where('status', 'ready')->count();
        $failed = count($this->generatedEmails) - $ready;
        Log::info("Campaign emails generated by user #" . Auth::id(), [
            'ready' => $ready, 'failed' => $failed,
        ]);
    }

    public function startEditEmail(int $index, int $emailNum): void
    {
        $this->editingIndex = $index;
        $this->editEmailNum = $emailNum;
        $this->editBuffer   = $this->generatedEmails[$index]["email{$emailNum}"] ?? '';
    }

    public function saveEmailEdit(): void
    {
        if ($this->editingIndex >= 0) {
            $this->generatedEmails[$this->editingIndex]["email{$this->editEmailNum}"] = $this->editBuffer;
        }
        $this->editingIndex = -1;
        $this->editBuffer   = '';
    }

    public function cancelEmailEdit(): void
    {
        $this->editingIndex = -1;
        $this->editBuffer   = '';
    }

    public function launchCampaign(): void
    {
        if (empty($this->generatedEmails)) {
            $this->error = 'No emails generated yet.';
            return;
        }

        $user = Auth::user();

        $campaign = Campaign::create([
            'user_id'              => $user->id,
            'name'                 => $this->name ?: 'Campaign ' . now()->format('M d'),
            'status'               => 'active',
            'daily_limit'          => $this->daily_limit,
            'gap_minutes'          => $this->gap_minutes,
            'start_date'           => $this->start_date ?: now()->toDateString(),
            'working_hours_start'  => $this->working_hours_start,
            'working_hours_end'    => $this->working_hours_end,
            'timezone'             => $this->timezone,
            'followup_delay_1'     => $this->followup_delay_1,
            'followup_delay_2'     => $this->followup_delay_2,
            'stop_on_reply'        => $this->stop_on_reply,
            'total_prospects'      => 0,
            'notes'                => $this->notes,
        ]);

        $service = app(CampaignService::class);
        $service->recordLog($campaign, 'created', "Campaign created by user #{$user->id}");

        // Persist prospects and build the payload CampaignService needs to schedule emails.
        // Scheduling rules (working hours/days, daily limit, gap, timezone, never-in-the-past)
        // live in one place only: CampaignService::scheduleEmails().
        $scheduleItems = [];

        foreach ($this->generatedEmails as $gen) {
            if (($gen['status'] ?? '') !== 'ready') continue;

            $email = trim($gen['prospect']['email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $prospect = Prospect::firstOrCreate(
                ['user_id' => $user->id, 'email' => $email],
                [
                    'name'          => $gen['prospect']['name'],
                    'company'       => $gen['prospect']['company'],
                    'role'          => $gen['prospect']['role'],
                    'industry'      => $gen['prospect']['industry'],
                    'pain_point'    => $gen['prospect']['pain_point'],
                    'personal_note' => $gen['prospect']['personal_note'] ?? null,
                ]
            );

            $scheduleItems[] = [
                'prospect_id' => $prospect->id,
                'subject1'    => $gen['subject1'] ?? '',
                'email1'      => $gen['email1'] ?? '',
                'email2'      => $gen['email2'] ?? '',
                'email3'      => $gen['email3'] ?? '',
                'status'      => 'ready',
            ];
        }

        if (empty($scheduleItems)) {
            $campaign->delete();
            $this->error = 'No prospects with a valid email address to launch.';
            return;
        }

        $campaign->update(['total_prospects' => count($scheduleItems)]);
        $service->scheduleEmails($campaign, $scheduleItems);

        $this->campaignId = $campaign->id;
        $this->step = 5; // Success step
    }

    public function nextStep(): void
    {
        $this->error = '';

        if ($this->step === 1) {
            if (empty($this->name)) {
                $this->error = 'Campaign name is required.';
                return;
            }
        }

        if ($this->step === 2) {
            if (empty($this->prospects)) {
                $this->error = 'Please upload a CSV file with prospects.';
                return;
            }
        }

        if ($this->step === 3) {
            $this->generateEmails();
            return;
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function render()
    {
        return view('livewire.campaign-create');
    }
}