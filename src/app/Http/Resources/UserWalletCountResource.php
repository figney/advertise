<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserWalletCountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'balance_withdraw' => (float)$this->balance_withdraw,
            'balance_recharge' => (float)$this->balance_withdraw,
            'balance_earnings' => (float)$this->balance_earnings,
            'balance_interest' => (float)$this->balance_interest,

            'usdt_balance_withdraw' => (float)$this->usdt_balance_withdraw,
            'usdt_balance_recharge' => (float)$this->usdt_balance_recharge,
            'usdt_balance_earnings' => (float)$this->usdt_balance_earnings,
            'usdt_balance_interest' => (float)$this->usdt_balance_interest,

            'give_balance_earnings' => (float)$this->give_balance_earnings,
            'give_balance_award' => (float)$this->give_balance_award,
        ];
    }
}
