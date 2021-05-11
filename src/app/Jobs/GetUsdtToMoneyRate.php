<?php

namespace App\Jobs;

use App\Enums\QueueType;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetUsdtToMoneyRate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = "https://www.binance.com/gateway-api/v2/public/ocbs/fiat-channel-gateway/get-quotation?tradeType=SELL&fiatCode=" . Setting('fiat_code') . "&cryptoAsset=USDT";

        $data = \Http::get($url)->object();

        $price = (float)data_get($data, "data.price", 0);

        if ($price > 0) {
            Setting::query()->update([
                'usdt_money_rate' => $price,
            ]);
            dispatch(new SocketIoToAll('rate_quotation', ['rate' => $price]))->onQueue(QueueType::allSend);
        } else {
            \Log::error("获取USDT汇率错误");
        }
    }
}
