<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BulkJob extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'total', 'processed',
        'failed', 'status', 'style', 'offer', 'value_prop', 'cta'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total === 0) return 0;
        return (int)(($this->processed / $this->total) * 100);
    }
}