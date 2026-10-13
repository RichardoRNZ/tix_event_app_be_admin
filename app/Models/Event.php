<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'events';
    protected $fillable = [
        'uuid',
        'venue_id',
        'name',
        'event_date',
        'desc',
        'code',
        'status',
        'max_capacity',
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

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function seatDetails()
    {
        return $this->hasMany(SeatDetail::class);
    }
}
