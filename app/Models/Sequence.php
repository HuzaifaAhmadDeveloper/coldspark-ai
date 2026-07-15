<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'user_id', 'prospect_id', 'style', 'offer',
        'value_prop', 'cta', 'subject1', 'subject2',
        'email1', 'email2', 'email3', 'credits_used'
    ];
    public function prospect() { return $this->belongsTo(Prospect::class); }
    public function user() { return $this->belongsTo(User::class); }
}