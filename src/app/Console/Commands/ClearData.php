<?php

namespace App\Console\Commands;

use App\Models\DeviceLog;
use App\Models\Notification;
use App\Models\UserSignInLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearData extends Command
{

    protected $signature = 'command:ClearData';


    protected $description = '清除无用数据';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $d = Notification::query()->where('created_at', '<', Carbon::now()->addDays(-5))->delete();
        \Log::info("删除Notification：" . $d);
        $ddl = DeviceLog::query()->where('created_at', '<', Carbon::now()->addDays(-3))->delete();
        \Log::info("删除DeviceLog：" . $ddl);
        $dddl = UserSignInLog::query()->where('created_at', '<', Carbon::now()->addDays(-15))->delete();
        \Log::info("删除UserSignInLog：" . $ddl);
    }
}
