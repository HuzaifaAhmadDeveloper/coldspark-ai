<?php
namespace App\Livewire;
use App\Models\Prospect;
use App\Models\Sequence;
use App\Services\GroqService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmailGenerator extends Component
{
    public string $name         = '';
    public string $company      = '';
    public string $role         = '';
    public string $industry     = '';
    public string $pain_point   = '';
    public string $personal_note = '';
    public string $offer        = '';
    public string $value_prop   = '';
    public string $cta          = 'Book a 15-min call';
    public string $sig_name    = '';
    public string $sig_role    = '';
    public string $sig_company = '';
    public string $sig_link    = '';
    public string $style        = 'direct';
    public array  $result       = [];
    public bool   $loading      = false;
    public string $error        = '';
    public bool   $saved        = false;
    public ?int   $selectedSubject = null;
    // Inline editing
    public bool   $editingEmail1  = false;
    public bool   $editingEmail2  = false;
    public bool   $editingEmail3  = false;
    public string $editBuffer1    = '';
    public string $editBuffer2    = '';
    public string $editBuffer3    = '';
    public bool   $hasUnsaved1    = false;
    public bool   $hasUnsaved2    = false;
    public bool   $hasUnsaved3    = false;
    public ?int   $savedSequenceId = null;

    public function generate(): void
    {
        $this->validate([
            'name'       => 'required|min:2',
            'company'    => 'required|min:2',
            'role'       => 'required',
            'offer'      => 'required',
            'value_prop' => 'required',
        ]);

        $user = Auth::user();
        if ($user->getCredits() <= 0) {
            $this->error = 'You have no credits left. Please upgrade your plan.';
            return;
        }

        $this->loading = true;
        $this->error   = '';
        $this->result  = [];
        $this->saved   = false;

        try {
            $groq = new GroqService();
            $res = $groq->generateSequence([
                'name'          => $this->name,
                'company'       => $this->company,
                'role'          => $this->role,
                'industry'      => $this->industry,
                'pain_point'    => $this->pain_point,
                'personal_note' => $this->personal_note,
                'offer'         => $this->offer,
                'value_prop'    => $this->value_prop,
                'cta'           => $this->cta,
                'style'         => $this->style,
                'sig_name'      => $this->sig_name,
                'sig_role'      => $this->sig_role,
                'sig_company'   => $this->sig_company,
                'sig_link'      => $this->sig_link,
            ]);

            if (empty($res)) {
                $this->error = 'Generation failed. Please try again.';
            } else {
                $this->result = $res;
                $this->result['ai_email1'] = $res['email1'] ?? '';
                $this->result['ai_email2'] = $res['email2'] ?? '';
                $this->result['ai_email3'] = $res['email3'] ?? '';
                $this->result['edited_email1'] = null;
                $this->result['edited_email2'] = null;
                $this->result['edited_email3'] = null;
                $user->deductCredit();
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function startEdit(int $num): void
    {
        $key = "editBuffer{$num}";
        $editedKey = "edited_email{$num}";
        $emailKey  = "email{$num}";
        $this->$key = !empty($this->result[$editedKey]) ? $this->result[$editedKey] : ($this->result[$emailKey] ?? '');
        $editing = "editingEmail{$num}";
        $this->$editing = true;
    }

    public function cancelEdit(int $num): void
    {
        $editing = "editingEmail{$num}";
        $this->$editing = false;
        $unsaved = "hasUnsaved{$num}";
        $this->$unsaved = false;
    }

    public function saveEdit(int $num): void
    {
        $bufferKey  = "editBuffer{$num}";
        $emailKey   = "email{$num}";
        $editedKey  = "edited_email{$num}";
        $editingKey = "editingEmail{$num}";
        $unsavedKey = "hasUnsaved{$num}";

        $this->result[$editedKey] = $this->$bufferKey;
        $this->result[$emailKey]  = $this->$bufferKey;
        $this->$editingKey = false;
        $this->$unsavedKey = false;

        // If already saved to DB — update there too
        if ($this->savedSequenceId) {
            $seq = \App\Models\Sequence::where('user_id', Auth::id())
                ->find($this->savedSequenceId);
            if ($seq) {
                $seq->update([
                    $editedKey  => $this->$bufferKey,
                    'is_edited' => true,
                ]);
            }
        }
    }

    public function resetToAI(int $num): void
    {
        $editingKey = "editingEmail{$num}";
        $unsavedKey = "hasUnsaved{$num}";
        $editedKey  = "edited_email{$num}";
        $aiKey      = "ai_email{$num}";
        $emailKey   = "email{$num}";

        $this->result[$editedKey] = null;
        if (isset($this->result[$aiKey])) {
            $this->result[$emailKey] = $this->result[$aiKey];
        }
        $bufferKey  = "editBuffer{$num}";
        $this->$bufferKey = $this->result[$emailKey] ?? '';

        $this->$editingKey = false;
        $this->$unsavedKey = false;

        if ($this->savedSequenceId) {
            $seq = \App\Models\Sequence::where('user_id', Auth::id())->find($this->savedSequenceId);
            if ($seq) {
                $seq->update([$editedKey => null]);
                $seq = $seq->fresh();
                if (empty($seq->edited_email1) && empty($seq->edited_email2) && empty($seq->edited_email3)) {
                    $seq->update(['is_edited' => false]);
                }
            }
        }
    }

    public function markUnsaved(int $num): void
    {
        $unsaved = "hasUnsaved{$num}";
        $this->$unsaved = true;
    }

    public function saveSequence(): void
    {
        if (empty($this->result)) return;

        $user = Auth::user();

        $prospect = Prospect::firstOrCreate(
            ['user_id' => $user->id, 'name' => $this->name, 'company' => $this->company],
            [
                'role'          => $this->role,
                'industry'      => $this->industry,
                'pain_point'    => $this->pain_point,
                'personal_note' => $this->personal_note,
            ]
        );

        $isEdited = !empty($this->result['edited_email1']) || !empty($this->result['edited_email2']) || !empty($this->result['edited_email3']);

        $seq = Sequence::create([
            'user_id'       => $user->id,
            'prospect_id'   => $prospect->id,
            'style'         => $this->style,
            'offer'         => $this->offer,
            'value_prop'    => $this->value_prop,
            'cta'           => $this->cta,
            'subject1'      => $this->result['subject1'] ?? '',
            'subject2'      => $this->result['subject2'] ?? '',
            'email1'        => $this->result['ai_email1'] ?? $this->result['email1'] ?? '',
            'email2'        => $this->result['ai_email2'] ?? $this->result['email2'] ?? '',
            'email3'        => $this->result['ai_email3'] ?? $this->result['email3'] ?? '',
            'edited_email1' => $this->result['edited_email1'] ?? null,
            'edited_email2' => $this->result['edited_email2'] ?? null,
            'edited_email3' => $this->result['edited_email3'] ?? null,
            'is_edited'     => $isEdited,
            'credits_used'  => 1,
        ]);

        $this->savedSequenceId = $seq->id;
        $this->saved = true;
}

    public function regenerateEmail(int $num): void
    {
        if (empty($this->result)) return;
        $this->loading = true;
        try {
            $groq  = new GroqService();
            $fresh = $groq->generateSequence([
                'name'          => $this->name,
                'company'       => $this->company,
                'role'          => $this->role,
                'industry'      => $this->industry,
                'pain_point'    => $this->pain_point,
                'personal_note' => $this->personal_note,
                'offer'         => $this->offer,
                'value_prop'    => $this->value_prop,
                'cta'           => $this->cta,
                'style'         => $this->style,
                'sig_name'      => $this->sig_name,
                'sig_role'      => $this->sig_role,
                'sig_company'   => $this->sig_company,
                'sig_link'      => $this->sig_link,
                    ]);
            $key = 'email' . $num;
            if (!empty($fresh[$key])) {
                $this->result[$key] = $fresh[$key];
            }
        } catch (\Exception $e) {
            $this->error = 'Regeneration failed.';
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.email-generator', [
            'credits' => Auth::user()->getCredits(),
        ]);
    }
}