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
          $this->result = $groq->generateSequence([
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

            if (empty($this->result)) {
                $this->error = 'Generation failed. Please try again.';
            } else {
                $user->deductCredit();
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }

        $this->loading = false;
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

        Sequence::create([
            'user_id'      => $user->id,
            'prospect_id'  => $prospect->id,
            'style'        => $this->style,
            'offer'        => $this->offer,
            'value_prop'   => $this->value_prop,
            'cta'          => $this->cta,
            'subject1'     => $this->result['subject1'] ?? '',
            'subject2'     => $this->result['subject2'] ?? '',
            'email1'       => $this->result['email1']   ?? '',
            'email2'       => $this->result['email2']   ?? '',
            'email3'       => $this->result['email3']   ?? '',
            'credits_used' => 1,
        ]);

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