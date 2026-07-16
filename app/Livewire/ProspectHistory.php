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