<?php

namespace App\Jobs;

use App\Models\UserProduct;
use App\Services\ProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductOver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userProduct;

    public function __construct(UserProduct $userProduct)
    {
        $this->userProduct = $userProduct;
    }


    public function handle()
    {
        ProductService::make()->productOver($this->userProduct);
    }
}
