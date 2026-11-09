<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model { protected $fillable = ['user_id','titre','message','lu_at']; protected $casts = ['lu_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); } }
