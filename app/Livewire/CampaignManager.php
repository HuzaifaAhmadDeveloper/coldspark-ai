<?php
namespace App\Livewire;
use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\Prospect;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CampaignManager extends Component
{
    public ?int     $viewingId  = null;
    public ?Campaign $viewing   = null;
    public string   $tab        = 'overview';

    public function viewCampaign(int $id): void
    {
        $this->viewing   = Campaign::where('user_id', Auth::id())->findOrFail($id);
        $this->viewingId = $id;
        $this->tab       = 'overview';
    }

    public function closeView(): void
    {
        $this->viewing   = null;
        $this->viewingId = null;
    }

    public function pauseCampaign(int $id): void
    {
        Campaign::where('user_id', Auth::id())->findOrFail($id)->update(['status' => 'paused']);
        if ($this->viewing?->id === $id) {
            $this->viewing = $this->viewing->fresh();
        }
    }

    public function resumeCampaign(int $id): void
    {
        Campaign::where('user_id', Auth::id())->findOrFail($id)->update(['status' => 'active']);
        if ($this->viewing?->id === $id) {
            $this->viewing = $this->viewing->fresh();
        }
    }

    public function cancelCampaign(int $id): void
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
        $campaign->update(['status' => 'cancelled']);
        CampaignEmail::where('campaign_id', $id)
            ->where('status', 'scheduled')
            ->update(['status' => 'skipped']);
        if ($this->viewing?->id === $id) {
            $this->viewing = $this->viewing->fresh();
        }
    }

    public function deleteCampaign(int $id): void
    {
        Campaign::where('user_id', Auth::id())->findOrFail($id)->delete();
        $this->viewing   = null;
        $this->viewingId = null;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    /**
     * Manual override for reply detection — mirrors ProspectHistory::markReplied.
     * The inbound webhook (see CampaignEmailWebhookController) covers ESPs with
     * inbound-parse routing configured; this is the fallback for everyone else.
     */
    public function markReplied(int $campaignEmailId): void
    {
        $email = CampaignEmail::whereHas('campaign', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($campaignEmailId);

        app(CampaignService::class)->markReply($email);

        if ($this->viewing?->id === $email->campaign_id) {
            $this->viewing = $this->viewing->fresh();
        }
    }

    public function render()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->latest()->get();

        $campaignIds = $campaigns->pluck('id');

        $e = CampaignEmail::whereIn('campaign_id', $campaignIds)
            ->selectRaw("
                SUM(status = 'scheduled') as scheduled,
                SUM(status = 'sending') as sending,
                SUM(status = 'sent') as sent,
                SUM(status = 'failed') as failed,
                SUM(opened = 1) as opened,
                SUM(clicked = 1) as clicked,
                SUM(replied = 1) as replied,
                SUM(bounced = 1) as bounced,
                SUM(delivered = 1) as delivered,
                SUM(status = 'skipped' AND email_number > 1) as cancelled_followups
            ")->first();

        $sent         = (int) ($e->sent ?? 0);
        $unsubscribed = Prospect::where('user_id', Auth::id())->where('unsubscribed', true)->count();

        $stats = [
            'total'               => $campaigns->count(),
            'active'              => $campaigns->where('status', 'active')->count(),
            'completed'           => $campaigns->where('status', 'completed')->count(),
            'scheduled'           => (int) ($e->scheduled ?? 0) + (int) ($e->sending ?? 0),
            'sent'                => $sent,
            'delivered'           => (int) ($e->delivered ?? 0),
            'opened'              => (int) ($e->opened ?? 0),
            'clicked'             => (int) ($e->clicked ?? 0),
            'replies'             => (int) ($e->replied ?? 0),
            'bounced'             => (int) ($e->bounced ?? 0),
            'failed'              => (int) ($e->failed ?? 0),
            'unsubscribed'        => $unsubscribed,
            'cancelled_followups' => (int) ($e->cancelled_followups ?? 0),
            'open_rate'           => $sent > 0 ? round(((int) ($e->opened ?? 0)) / $sent * 100, 1) : 0,
            'reply_rate'          => $sent > 0 ? round(((int) ($e->replied ?? 0)) / $sent * 100, 1) : 0,
        ];

        $emails = $this->viewing
            ? CampaignEmail::with('prospect')
                ->where('campaign_id', $this->viewing->id)
                ->orderBy('scheduled_at')
                ->get()
            : collect();

        return view('livewire.campaign-manager', compact('campaigns', 'stats', 'emails'));
    }
}