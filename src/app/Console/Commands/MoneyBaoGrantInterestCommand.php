<?php

namespace App\Console\Commands;

use App\Services\MoneyBaoService;
use Illuminate\Console\Command;

class MoneyBaoGrantInterestCommand extends Command
{

    protected $signature = 'command:MoneyBaoGrantInterest';


    protected $description = '发放赚钱宝利息';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {


        MoneyBaoService::make()->checkGrantInterest();

        $info = "发放赚钱宝利息执行完成：" . now()->toDateTimeString();

        \Log::info($info);

    }
}
