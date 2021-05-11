<?php

namespace App\Jobs;

use App\Models\RechargeChannel;
use App\Services\LaoSunService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetPayZjrAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected RechargeChannel $rechargeChannel)
    {

    }


    public function handle()
    {
        //LaoSunService::make()->setChannel($this->rechargeChannel)->createAddress($this->rechargeChannel->id);
    }
}
