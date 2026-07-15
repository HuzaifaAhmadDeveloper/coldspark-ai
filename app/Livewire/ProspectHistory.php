<?php
namespace App\Livewire;
use App\Models\Sequence;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProspectHistory extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $viewingId = null;
    public ?Sequence $viewing = null;

    public function viewSequence(int $id): void
    {
        $this->viewing   = Sequence::with('prospect')
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewing   = null;
        $this->viewingId = null;
    }

    public function deleteSequence(int $id): void
    {
        Sequence::where('user_id', Auth::id())->findOrFail($id)->delete();
        $this->viewing   = null;
        $this->viewingId = null;
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
            ->latest()
            ->paginate(10);

        return view('livewire.prospect-history', compact('sequences'));
    }
}