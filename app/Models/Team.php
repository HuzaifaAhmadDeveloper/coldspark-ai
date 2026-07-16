<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = ['owner_id', 'name', 'invite_code'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function members() { return $this->hasMany(TeamMember::class); }
    public function users() { return $this->hasManyThrough(User::class, TeamMember::class, 'team_id', 'id', 'id', 'user_id'); }

    public static function generateInviteCode(): string {
        return strtoupper(Str::random(8));
    }
}