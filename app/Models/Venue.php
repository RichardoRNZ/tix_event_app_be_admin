<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'venues';
    protected $fillable = [
        'uuid',
        'name',
        'desc',
        'location',
        'address',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'location' => 'string',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
