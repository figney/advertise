<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;


class Article extends Model implements Sortable
{
    use HasDateTimeFormatter, SortableTrait,Cachable;

    protected $sortable = [
        // 设置排序字段名称
        'order_column_name' => 'order',
        // 是否在创建时自动排序，此参数建议设置为true
        'sort_when_creating' => true,
    ];


    protected $casts = [
        'title' => 'json',
        'describe' => 'json',
        'content' => 'json',
    ];

    const TYPE = [
        'help'
    ];

}
