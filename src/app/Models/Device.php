<?php

namespace App\Models;


use App\Models\Traits\AdminDataScope;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

class Device extends Model
{
    use HasDateTimeFormatter,AdminDataScope;

    protected $connection = "mongodb";


    protected $guarded = [];

    protected $casts = [

    ];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function ips()
    {
        return $this->hasMany(Device::class, 'ip', 'ip');
    }


}
