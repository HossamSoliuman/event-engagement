<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Vote extends Model
{
    protected $fillable = ['event_id','candidate_name','candidate_slug','voter_session','voter_ip'];
    public function event() { return $this->belongsTo(Event::class); }
}
