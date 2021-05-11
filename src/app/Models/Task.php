<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;


class Task extends Model implements Sortable
{
    use HasDateTimeFormatter, HybridRelations, SortableTrait;

    protected $connection = "mysql";

    protected $guarded = [];

    protected $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];
    protected $casts = [
        'repetition' => 'bool',
        //'is_user_award' => 'bool',
        //'is_parent_award' => 'bool',
        'is_deduct' => 'bool',
        'auto_get' => 'bool',
        'is_show_alert' => 'bool',
        'check_withdraw' => 'bool',
        'status' => 'bool',
        'title' => 'json',
        'describe' => 'json',
        'btn_name' => 'json',
        'content' => 'json',
    ];

    public function userTask()
    {
        return $this->hasOne(UserTask::class)->orderByDesc('created_at');
    }


}
