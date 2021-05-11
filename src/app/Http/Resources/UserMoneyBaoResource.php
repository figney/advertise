<?php

namespace App\Http\Resources;

use App\Models\MoneyBao;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMoneyBaoResource extends JsonResource
{

    public function toArray($request)
    {

        /** @var MoneyBao|JsonResource $this */

        return [
            'balance' => (float)$this->balance,
            'usdt_balance' => (float)$this->usdt_balance,
            'give_balance' => (float)$this->give_balance,
            'money_bao_count' => $this->MoneyBaoCount,
            'day_date' => $this->dayData(),
            'day_status' => $this->dayStatus(),
        ];
    }
}
