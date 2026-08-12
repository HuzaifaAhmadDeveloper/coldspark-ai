<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    protected $fillable = [
        'sequence_id',
        'campaign_email_id',
        'event_type',
        'metadata',
        'provider_event_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function campaignEmail()
    {
        return $this->belongsTo(CampaignEmail::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
