<?php
namespace App\Livewire;
use App\Models\BulkJob;
use App\Models\Prospect;
use App\Models\Sequence;
use App\Services\GroqService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BulkEmailGenerator extends Component
{
    // Remove WithFileUploads
    public string $style      = 'direct';
    public string $offer      = '';
    public string $value_prop = '';
    public string $cta        = 'Book a 15-min call';
    public array  $preview    = [];
    public array  $results    = [];
    public bool   $processing = false;
    public bool   $done       = false;
    public string $sig_name    = '';
    public string $sig_role    = '';
    public string $sig_company = '';
    public string $sig_link    = '';
    public int    $progress   = 0;
    public int    $total      = 0;
    public int    $failed     = 0;
    public string $error      = '';
    public ?int   $jobId      = null;
    public string $csvData    = ''; // CSV data from browser

    public function loadCsvData(string $csvContent): void
    {
        $this->preview = [];
        $this->results = [];
        $this->done    = false;
        $this->error   = '';

        $rows    = array_map('str_getcsv', explode("\n", trim($csvContent)));
        $headers = array_map('strtolower', array_map('trim', $rows[0]));

        $count = 0;
        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 2) continue;
            $data = array_combine($headers, array_pad($row, count($headers), ''));
            $this->preview[] = [
                'name'          => $data['name']       ?? '',
                'company'       => $data['company']    ?? '',
                'role'          => $data['role']       ?? $data['title'] ?? '',
                'industry'      => $data['industry']   ?? '',
                'pain_point'    => $data['pain']       ?? $data['pain_point'] ?? '',
                'personal_note' => $data['note']       ?? '',
            ];
            if (++$count >= 50) break;
        }
        $this->total = count($this->preview);
    }

    public function process(): void
    {
        if (empty($this->preview)) {
            $this->error = 'Please upload a CSV file first.';
            return;
        }
        if (empty($this->offer) || empty($this->value_prop)) {
            $this->error = 'Please fill offer and value proposition.';
            return;
        }

        $user = Auth::user();
        if ($user->getCredits() < $this->total) {
            $this->error = "Not enough credits. Need {$this->total}, have {$user->getCredits()}.";
            return;
        }

        $this->processing = true;
        $this->results    = [];
        $this->progress   = 0;
        $this->failed     = 0;
        $this->error      = '';

        $job = BulkJob::create([
            'user_id'    => $user->id,
            'filename'   => 'upload.csv',
            'total'      => $this->total,
            'status'     => 'processing',
            'style'      => $this->style,
            'offer'      => $this->offer,
            'value_prop' => $this->value_prop,
            'cta'        => $this->cta,
        ]);
        $this->jobId = $job->id;

        $groq = new GroqService();

        foreach ($this->preview as $row) {
            try {
                $result = $groq->generateSequence([
                'name'          => $row['name'],
                'company'       => $row['company'],
                'role'          => $row['role'],
                'industry'      => $row['industry'],
                'pain_point'    => $row['pain_point'],
                'personal_note' => $row['personal_note'],
                'offer'         => $this->offer,
                'value_prop'    => $this->value_prop,
                'cta'           => $this->cta,
                'style'         => $this->style,
                'sig_name'      => $this->sig_name,
                'sig_role'      => $this->sig_role,
                'sig_company'   => $this->sig_company,
                'sig_link'      => $this->sig_link,
            ]);

                if (!empty($result)) {
                    $prospect = Prospect::firstOrCreate(
                        ['user_id' => $user->id, 'name' => $row['name'], 'company' => $row['company']],
                        ['role' => $row['role'], 'industry' => $row['industry'], 'pain_point' => $row['pain_point']]
                    );
                    Sequence::create([
                        'user_id'      => $user->id,
                        'prospect_id'  => $prospect->id,
                        'style'        => $this->style,
                        'offer'        => $this->offer,
                        'value_prop'   => $this->value_prop,
                        'cta'          => $this->cta,
                        'subject1'     => $result['subject1'] ?? '',
                        'subject2'     => $result['subject2'] ?? '',
                        'email1'       => $result['email1']   ?? '',
                        'email2'       => $result['email2']   ?? '',
                        'email3'       => $result['email3']   ?? '',
                        'credits_used' => 1,
                    ]);
                    $user->deductCredit();
                    $this->results[] = array_merge($row, $result, ['status' => 'success']);
                } else {
                    $this->results[] = array_merge($row, ['status' => 'failed']);
                    $this->failed++;
                }
            } catch (\Exception $e) {
                $this->results[] = array_merge($row, ['status' => 'failed']);
                $this->failed++;
            }
            $this->progress++;
            $job->update(['processed' => $this->progress, 'failed' => $this->failed]);
        }

        $job->update(['status' => 'completed']);
        $this->processing = false;
        $this->done       = true;
    }

    public function render()
    {
        return view('livewire.bulk-email-generator', [
            'credits' => Auth::user()->getCredits(),
        ]);
    }
}