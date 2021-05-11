<?php

namespace App\Console\Commands;

use App\Services\ProductService;
use Illuminate\Console\Command;

class CheckDayProductOver extends Command
{

    protected $signature = 'command:CheckDayProductOver';


    protected $description = '每日结算产品结束检测';


    public function __construct()
    {
        parent::__construct();

    }


    public function handle()
    {


        ProductService::make()->checkDayProductOver();



    }
}
