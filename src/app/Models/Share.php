<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;


class Share extends Model
{
    use HasDateTimeFormatter;

    public $timestamps = false;

    protected $casts = [
        'title' => 'json',
        'describe' => 'json',
        'cover' => 'json',
        'status' => 'bool',
    ];


    protected static function booted()
    {
        static::created(function () {
            \Cache::tags(['SHARE'])->flush();
        });
        static::updated(function () {
            \Cache::tags(['SHARE'])->flush();
        });
    }
}
