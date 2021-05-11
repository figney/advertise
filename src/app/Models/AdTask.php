<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;


class AdTask extends Model
{
    use HasDateTimeFormatter, HybridRelations;

    protected $table = 'ad_tasks';

    protected $connection = "mysql";

    protected $casts = [
        'vip_level_max_config' => 'json',
        'tags' => 'json',
        'commission_config' => 'json',

    ];


    public function adData()
    {
        return $this->hasOne(AdTaskData::class);
    }


    public function userAdTask()
    {
        return $this->hasOne(UserAdTask::class);
    }

    public function userAdTaskList()
    {
        return $this->hasMany(UserAdTask::class);
    }

    protected static function booted()
    {
        self::deleting(function (AdTask $adTask) {
            abort_if($adTask->userAdTaskList()->count() > 0, 400, '当前任务无法删除');
        });

        self::deleted(function (AdTask $adTask) {
            $adTask->adData()->delete();
        });
    }

}
