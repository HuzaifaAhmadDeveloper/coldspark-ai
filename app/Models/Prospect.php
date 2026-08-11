<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'company', 'role',
        'industry', 'pain_point', 'personal_note',
        'unsubscribed', 'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed'    => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];

    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaignEmails()
    {
        return $this->hasMany(CampaignEmail::class);
    }
}