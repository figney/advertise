<?php

namespace App\Jobs;

use App\Models\MoneyBao;
use App\Services\MoneyBaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MoneyBaoGrantInterest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected MoneyBao $moneyBao;


    public function __construct(MoneyBao $moneyBao)
    {
        $this->moneyBao = $moneyBao;
    }


    public function handle()
    {

        MoneyBaoService::make()->grantInterest($this->moneyBao);

    }
}
