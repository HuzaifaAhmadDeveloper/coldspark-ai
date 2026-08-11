<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppression extends Model
{
    protected $fillable = [
        'user_id', 'email', 'reason', 'source_campaign_email_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceCampaignEmail()
    {
        return $this->belongsTo(CampaignEmail::class, 'source_campaign_email_id');
    }
}
