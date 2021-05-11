<?php

namespace App\Models;


use App\Models\Traits\AdminDataScope;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class DeviceLog extends Model
{
    use HasDateTimeFormatter, AdminDataScope;

    protected $connection = "mysql";

    protected $table = "device_logs";


    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
