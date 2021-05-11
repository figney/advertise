<?php

namespace App\Console\Commands;

use App\Services\ProductService;
use Illuminate\Console\Command;

class ProductOver extends Command
{

    protected $signature = 'command:ProductOver';


    protected $description = '定期投资产品结算';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        ProductService::make()->checkProductOver();
    }
}
