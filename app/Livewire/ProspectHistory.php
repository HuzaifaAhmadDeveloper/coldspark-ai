<?php
namespace App\Livewire;
use App\Models\Sequence;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProspectHistory extends Component
{
    use WithPagination;

    public string  $search      = '';
    public string  $filterReply = 'all';
    public ?int    $viewingId   = null;
    public ?Sequence $viewing   = null;

    // Reply tracking
    public string  $replyNotes  = '';
    public string  $replyStatus = 'positive';
    public bool    $showReplyForm = false;
    // Inline editing for history
    public bool   $editingHistEmail1 = false;
    public bool   $editingHistEmail2 = false;
    public bool   $editingHistEmail3 = false;
    public string $histEditBuffer1   = '';
    public string $histEditBuffer2   = '';
    public string $histEditBuffer3   = '';

    public function viewSequence(int $id): void
    {
        $this->viewing      = Sequence::with('prospect')
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        $this->viewingId    = $id;
        $this->replyNotes   = $this->viewing->reply_notes ?? '';
        $this->replyStatus  = $this->viewing->reply_status ?? 'positive';
        $this->showReplyForm = false;
    }

    public function closeView(): void
    {
        $this->viewing      = null;
        $this->viewingId    = null;
        $this->showReplyForm = false;
    }

    public function deleteSequence(int $id): void
    {
        Sequence::where('user_id', Auth::id())->findOrFail($id)->delete();
        $this->viewing      = null;
        $this->viewingId    = null;
    }

    public function markReplied(): void
    {
        if (!$this->viewing) return;

        $this->viewing->update([
            'replied'      => true,
            'replied_at'   => now(),
            'reply_notes'  => $this->replyNotes,
            'reply_status' => $this->replyStatus,
        ]);

        $this->viewing       = $this->viewing->fresh();
        $this->showReplyForm = false;
    }

    public function startHistEdit(int $num): void
{
    $buffer  = "histEditBuffer{$num}";
    $key     = "email{$num}";
    $editing = "editingHistEmail{$num}";
    $this->$buffer  = $this->viewing->getDisplayEmail($num);
    $this->$editing = true;
}

public function cancelHistEdit(int $num): void
{
    $editing = "editingHistEmail{$num}";
    $this->$editing = false;
}

public function saveHistEdit(int $num): void
{
    if (!$this->viewing) return;

    $buffer    = "histEditBuffer{$num}";
    $editedKey = "edited_email{$num}";
    $editing   = "editingHistEmail{$num}";

    $this->viewing->update([
        $editedKey  => $this->$buffer,
        'is_edited' => true,
    ]);

    $this->viewing   = $this->viewing->fresh();
    $this->$editing  = false;
}

public function resetHistToAI(int $num): void
{
    if (!$this->viewing) return;

    $editedKey = "edited_email{$num}";
    $editing   = "editingHistEmail{$num}";

    $this->viewing->update([$editedKey => null]);

    // Check if any email still edited
    $seq = $this->viewing->fresh();
    if (empty($seq->edited_email1) && empty($seq->edited_email2) && empty($seq->edited_email3)) {
        $seq->update(['is_edited' => false]);
    }

    $this->viewing  = $seq;
    $this->$editing = false;
}

    public function markNoReply(): void
    {
        if (!$this->viewing) return;
        $this->viewing->update([
            'replied'      => false,
            'replied_at'   => null,
            'reply_notes'  => '',
            'reply_status' => 'none',
        ]);
        $this->viewing = $this->viewing->fresh();
    }

    public function render()
    {
        $sequences = Sequence::with('prospect')
            ->where('user_id', Auth::id())
            ->when($this->search, fn($q) =>
                $q->whereHas('prospect', fn($p) =>
                    $p->where('name', 'like', "%{$this->search}%")
                      ->orWhere('company', 'like', "%{$this->search}%")
                ))
            ->when($this->filterReply === 'replied', fn($q) => $q->where('replied', true))
            ->when($this->filterReply === 'no_reply', fn($q) => $q->where('replied', false))
            ->latest()
            ->paginate(10);

        $stats = [
            'total'   => Sequence::where('user_id', Auth::id())->count(),
            'replied' => Sequence::where('user_id', Auth::id())->where('replied', true)->count(),
            'positive'=> Sequence::where('user_id', Auth::id())->where('reply_status', 'positive')->count(),
        ];

        return view('livewire.prospect-history', compact('sequences', 'stats'));
    }
}