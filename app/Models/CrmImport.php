<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CrmImport extends Model
{
    protected $fillable = [
        'user_id', 'crm_type', 'filename',
        'total_contacts', 'imported', 'skipped', 'status'
    ];

    public function user() { return $this->belongsTo(User::class); }
}