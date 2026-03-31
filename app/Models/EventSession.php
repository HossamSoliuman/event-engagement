<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class EventSession extends Model
{
    protected $fillable = ['event_id','session_token','guest_name','guest_phone','ip_address','user_agent','actions_taken','last_active_at'];
    protected $casts = ['last_active_at'=>'datetime','actions_taken'=>'array'];
    public function event() { return $this->belongsTo(Event::class); }
    public static function startSession(Event $event, array $data=[]): static {
        return static::create(array_merge(['event_id'=>$event->id,'session_token'=>Str::random(64),'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent(),'last_active_at'=>now()], $data));
    }
}
