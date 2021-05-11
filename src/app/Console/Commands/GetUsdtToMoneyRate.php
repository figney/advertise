<?php

namespace App\Console\Commands;

use App\Enums\QueueType;
use Illuminate\Console\Command;

class GetUsdtToMoneyRate extends Command
{

    protected $signature = 'command:GetUsdtToMoneyRate';


    protected $description = '获取实时汇率';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        dispatch(new \App\Jobs\GetUsdtToMoneyRate())->onQueue(QueueType::request);
    }
}
