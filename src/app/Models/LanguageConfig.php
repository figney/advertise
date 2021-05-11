<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;


class LanguageConfig extends Model
{
    use  Cachable;


    protected $table = 'language_config_v2';

    protected $primaryKey = "slug";

    public $timestamps = false;

    protected $casts = [
        'content' => 'json',
        'slug' => 'string',
    ];

    protected $guarded = [];


    public static function AllGroup(): array
    {
        return self::query()->groupBy('group')->select(['group'])->pluck('group', 'group')->toArray();
    }

    protected static function booted()
    {
        /* static::updated(function () {
             \Cache::tags(self::CACHE_TAG)->flush();
         });
         static::created(function () {
             \Cache::tags(self::CACHE_TAG)->flush();
         });*/
    }

}
