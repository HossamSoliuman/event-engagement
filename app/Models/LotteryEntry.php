<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LotteryEntry extends Model
{
    protected $fillable = ['event_id','name','phone','email','is_winner','won_at','entry_token'];
    protected $casts = ['is_winner'=>'boolean','won_at'=>'datetime'];
    public function event() { return $this->belongsTo(Event::class); }
}
