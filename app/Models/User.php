<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function prospects() { return $this->hasMany(Prospect::class); }
    public function sequences() { return $this->hasMany(Sequence::class); }
    public function credit() { return $this->hasOne(Credit::class); }

    public function getCredits(): int {
        return $this->credit?->balance ?? 0;
    }

    public function deductCredit(): bool {
        if ($this->getCredits() <= 0) return false;
        $this->credit->decrement('balance');
        return true;
    }

    public function team() {
    return $this->hasOne(TeamMember::class);
}

public function ownedTeam() {
    return $this->hasOne(Team::class, 'owner_id');
}

public function currentTeam() {
    return $this->team?->team;
}

    public function getPlanName(): string {
        if ($this->subscribed('default')) {
            if ($this->subscribedToPrice(env('STRIPE_BUSINESS_PRICE'), 'default')) {
                return 'Business';
            }
            return 'Pro';
        }
        return 'Basic';
    }

    public function addCredits(int $amount): void {
        if ($this->credit) {
            $this->credit->increment('balance', $amount);
        } else {
            Credit::create(['user_id' => $this->id, 'balance' => $amount]);
        }
    }
}