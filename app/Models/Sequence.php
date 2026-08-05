<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'user_id', 'prospect_id', 'style', 'offer',
        'value_prop', 'cta', 'subject1', 'subject2',
        'email1', 'email2', 'email3', 'credits_used',
        'replied', 'replied_at', 'reply_notes', 'reply_status',
        'edited_email1', 'edited_email2', 'edited_email3', 'is_edited'
    ];

    protected $casts = [
        'replied'    => 'boolean',
        'replied_at' => 'datetime',
        'is_edited'  => 'boolean',
    ];

    public function prospect() { return $this->belongsTo(Prospect::class); }
    public function user() { return $this->belongsTo(User::class); }

    // Helper: get display email (edited if exists, else AI original)
    public function getDisplayEmail(int $num): string
    {
        $edited   = "edited_email{$num}";
        $original = "email{$num}";
        return !empty($this->$edited) ? $this->$edited : ($this->$original ?? '');
    }

    public function isEmailEdited(int $num): bool
    {
        $edited = "edited_email{$num}";
        return !empty($this->$edited);
    }
}