<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
}