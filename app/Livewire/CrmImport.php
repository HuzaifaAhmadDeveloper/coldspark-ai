<?php
namespace App\Livewire;
use App\Models\CrmImport as CrmImportModel;
use App\Models\Prospect;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrmImport extends Component
{
    use WithFileUploads;

    public $file;
    public string $crmType    = 'hubspot';
    public array  $preview    = [];
    public array  $imported   = [];
    public bool   $processing = false;
    public bool   $done       = false;
    public string $error      = '';
    public string $success    = '';
    public int    $total      = 0;
    public int    $importedCount = 0;
    public int    $skippedCount  = 0;
    public array  $history    = [];

    // CRM field mappings
    private array $fieldMaps = [
        'hubspot' => [
            'name'      => ['First Name', 'Last Name', 'firstname', 'lastname', 'Contact Name', 'name'],
            'company'   => ['Company', 'company', 'Company Name'],
            'role'      => ['Job Title', 'jobtitle', 'Title', 'title'],
            'industry'  => ['Industry', 'industry'],
            'email'     => ['Email', 'email', 'Email Address'],
            'phone'     => ['Phone Number', 'phone', 'Phone'],
        ],
        'salesforce' => [
            'name'      => ['Name', 'Full Name', 'FirstName', 'LastName'],
            'company'   => ['Account Name', 'Company'],
            'role'      => ['Title', 'Job Title'],
            'industry'  => ['Industry'],
            'email'     => ['Email'],
            'phone'     => ['Phone', 'Mobile'],
        ],
        'pipedrive' => [
            'name'      => ['Name', 'Contact Name', 'Person Name'],
            'company'   => ['Organization', 'Company', 'Organization Name'],
            'role'      => ['Job Title', 'Title'],
            'industry'  => ['Industry', 'Label'],
            'email'     => ['Email', 'email'],
            'phone'     => ['Phone', 'phone'],
        ],
        'zoho' => [
            'name'      => ['First Name', 'Last Name', 'Full Name'],
            'company'   => ['Account Name', 'Company'],
            'role'      => ['Title', 'Designation'],
            'industry'  => ['Industry'],
            'email'     => ['Email', 'Email Opt Out'],
            'phone'     => ['Phone', 'Mobile'],
        ],
        'csv' => [
            'name'      => ['name', 'full_name', 'contact_name', 'Name'],
            'company'   => ['company', 'company_name', 'organization', 'Company'],
            'role'      => ['role', 'title', 'job_title', 'position', 'Role'],
            'industry'  => ['industry', 'sector', 'Industry'],
            'email'     => ['email', 'email_address', 'Email'],
            'phone'     => ['phone', 'phone_number', 'mobile', 'Phone'],
        ],
    ];

    public function mount(): void
    {
        $this->history = CrmImportModel::where('user_id', Auth::id())
            ->latest()->take(5)->get()->toArray();
    }

    public function updatedFile(): void
    {
        $this->validate(['file' => 'required|mimes:csv,txt|max:5120']);
        $this->preview    = [];
        $this->error      = '';
        $this->done       = false;

        try {
            $path    = $this->file->getRealPath();
            $rows    = array_map('str_getcsv', file($path));
            $headers = array_map('trim', $rows[0]);

            $count = 0;
            foreach (array_slice($rows, 1) as $row) {
                if (count($row) < 2) continue;
                $data = array_combine($headers, array_pad($row, count($headers), ''));
                $mapped = $this->mapFields($data, $headers);
                if (!empty($mapped['name']) || !empty($mapped['company'])) {
                    $this->preview[] = $mapped;
                    $count++;
                }
                if ($count >= 100) break;
            }
            $this->total = count($this->preview);
        } catch (\Exception $e) {
            $this->error = 'Could not parse file: ' . $e->getMessage();
        }
    }

    private function mapFields(array $data, array $headers): array
    {
        $map    = $this->fieldMaps[$this->crmType] ?? $this->fieldMaps['csv'];
        $result = ['name'=>'','company'=>'','role'=>'','industry'=>'','email'=>'','phone'=>''];

        foreach ($result as $field => $val) {
            foreach ($map[$field] ?? [] as $possible) {
                foreach ($headers as $header) {
                    if (strtolower(trim($header)) === strtolower($possible)) {
                        $result[$field] = trim($data[$header] ?? '');
                        break 2;
                    }
                }
            }
        }

        // Build full name if split
        if (empty($result['name'])) {
            $first = '';
            $last  = '';
            foreach ($headers as $h) {
                if (in_array(strtolower(trim($h)), ['first name','firstname','first_name'])) $first = trim($data[$h] ?? '');
                if (in_array(strtolower(trim($h)), ['last name','lastname','last_name']))  $last  = trim($data[$h] ?? '');
            }
            if ($first || $last) $result['name'] = trim("$first $last");
        }

        return $result;
    }

    public function importContacts(): void
    {
        if (empty($this->preview)) {
            $this->error = 'Please upload a file first.';
            return;
        }

        $this->processing    = true;
        $this->importedCount = 0;
        $this->skippedCount  = 0;
        $this->error         = '';

        $user = Auth::user();

        $crm = CrmImportModel::create([
            'user_id'        => $user->id,
            'crm_type'       => $this->crmType,
            'filename'       => $this->file->getClientOriginalName(),
            'total_contacts' => $this->total,
            'status'         => 'processing',
        ]);

        foreach ($this->preview as $contact) {
            if (empty($contact['name']) && empty($contact['company'])) {
                $this->skippedCount++;
                continue;
            }

            $existing = Prospect::where('user_id', $user->id)
                ->where(function($q) use ($contact) {
                    $q->where('name', $contact['name'])
                      ->orWhere('company', $contact['company']);
                })->first();

            if ($existing) {
                $this->skippedCount++;
                continue;
            }

            Prospect::create([
                'user_id'  => $user->id,
                'name'     => $contact['name']     ?: ($contact['company'] . ' Contact'),
                'company'  => $contact['company']  ?: 'Unknown',
                'role'     => $contact['role']     ?: '',
                'industry' => $contact['industry'] ?: '',
                'pain_point'    => '',
                'personal_note' => !empty($contact['email']) ? 'Email: ' . $contact['email'] : '',
            ]);

            $this->importedCount++;
        }

        $crm->update([
            'imported' => $this->importedCount,
            'skipped'  => $this->skippedCount,
            'status'   => 'completed',
        ]);

        $this->processing = false;
        $this->done       = true;
        $this->success    = "✅ {$this->importedCount} contacts imported! {$this->skippedCount} duplicates skipped.";

        $this->history = CrmImportModel::where('user_id', $user->id)
            ->latest()->take(5)->get()->toArray();
    }

    public function render()
    {
        return view('livewire.crm-import');
    }
}