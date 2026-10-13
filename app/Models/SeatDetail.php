<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatDetail extends Model
{
    use HasFactory;
    protected $table = 'seat_details';
    
    protected $fillable = [
        'uuid',
        'seat_id',
        'position',
        'event_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
