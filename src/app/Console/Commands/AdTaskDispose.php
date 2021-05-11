<?php

namespace App\Console\Commands;

use App\Services\AdTaskService;
use Illuminate\Console\Command;

class AdTaskDispose extends Command
{

    protected $signature = 'command:AdTaskDispose';


    protected $description = '处理广告任务数据';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        AdTaskService::make()->disposeUserAdTask();
    }
}
