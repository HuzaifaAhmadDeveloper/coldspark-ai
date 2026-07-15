<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    protected $fillable = [
        'user_id', 'name', 'company', 'role',
        'industry', 'pain_point', 'personal_note'
    ];
    public function sequences() { return $this->hasMany(Sequence::class); }
    public function user() { return $this->belongsTo(User::class); }
}