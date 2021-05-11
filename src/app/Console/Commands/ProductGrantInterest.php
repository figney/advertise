<?php

namespace App\Console\Commands;

use App\Services\ProductService;
use Illuminate\Console\Command;

class ProductGrantInterest extends Command
{

    protected $signature = 'command:ProductGrantInterest';


    protected $description = '定期投资产品利息发放';


    public function __construct()
    {
        parent::__construct();

    }


    public function handle()
    {


        ProductService::make()->checkProductGrantInterest();




    }
}
