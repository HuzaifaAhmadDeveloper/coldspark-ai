<?php
namespace App\Livewire;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamManager extends Component
{
    public string $teamName   = '';
    public string $inviteCode = '';
    public string $error      = '';
    public string $success    = '';
    public ?Team  $team       = null;

    public function mount(): void
    {
        $user = Auth::user();
        // Check if user owns a team
        $this->team = Team::where('owner_id', $user->id)->first();
        // Or is a member
        if (!$this->team) {
            $member = TeamMember::where('user_id', $user->id)->first();
            $this->team = $member?->team;
        }
    }

    public function createTeam(): void
    {
        $this->validate(['teamName' => 'required|min:3|max:50']);
        $user = Auth::user();

        if ($this->team) {
            $this->error = 'You are already in a team.';
            return;
        }

        $team = Team::create([
            'owner_id'    => $user->id,
            'name'        => $this->teamName,
            'invite_code' => Team::generateInviteCode(),
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => 'owner',
        ]);

        $this->team    = $team;
        $this->success = 'Team created successfully!';
        $this->teamName = '';
    }

    public function joinTeam(): void
    {
        $this->validate(['inviteCode' => 'required|size:8']);
        $user = Auth::user();

        if ($this->team) {
            $this->error = 'You are already in a team.';
            return;
        }

        $team = Team::where('invite_code', strtoupper($this->inviteCode))->first();

        if (!$team) {
            $this->error = 'Invalid invite code.';
            return;
        }

        $alreadyMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)->exists();

        if ($alreadyMember) {
            $this->error = 'You are already a member of this team.';
            return;
        }

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => 'member',
        ]);

        $this->team    = $team->fresh();
        $this->success = 'Joined team successfully!';
        $this->inviteCode = '';
    }

    public function leaveTeam(): void
    {
        $user = Auth::user();
        if (!$this->team) return;

        if ($this->team->owner_id === $user->id) {
            // Owner dissolves the team
            TeamMember::where('team_id', $this->team->id)->delete();
            $this->team->delete();
        } else {
            TeamMember::where('team_id', $this->team->id)
                ->where('user_id', $user->id)->delete();
        }

        $this->team    = null;
        $this->success = 'Left team successfully.';
    }

    public function regenerateCode(): void
    {
        if (!$this->team || $this->team->owner_id !== Auth::id()) return;
        $this->team->update(['invite_code' => Team::generateInviteCode()]);
        $this->team = $this->team->fresh();
        $this->success = 'Invite code regenerated!';
    }

    public function render()
    {
        $members = $this->team
            ? TeamMember::with('user')->where('team_id', $this->team->id)->get()
            : collect();

        return view('livewire.team-manager', compact('members'));
    }
}