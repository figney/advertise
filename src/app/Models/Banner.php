<?php


namespace App\Models;


use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Banner extends Model implements Sortable
{
    use SortableTrait,Cachable;

    protected $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];

    protected $casts = [
        'describe' => 'json',
        'image' => 'json',
        'link_name' => 'json',
        'title' => 'json',
    ];


    protected $guarded = [];

}
