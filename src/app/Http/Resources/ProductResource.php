<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{

    public function toArray($request)
    {
        /**@var Product|JsonResource $this */
        return [
            'id' => $this->id,
            'title' => LocalDataGet($this->title),
            'describe' => LocalDataGet($this->describe),
            'content' => $this->when(!in_array('content', $this->getHidden()), LocalDataGet($this->content)),
            'big_cover' => $this->when(!in_array('big_cover', $this->getHidden()), ImageUrl($this->big_cover)),
            'select_money_list' => $this->when(!in_array('select_money_list', $this->getHidden()), $this->getSelectMoneyList()),
            'cover' => ImageUrl($this->cover),
            'day_rate' => (float)$this->day_rate,
            'day_cycle' => (int)$this->day_cycle,
            'is_no_buy_user' => (bool)$this->is_no_buy_user,
            'user_max_buy' => (int)$this->user_max_buy,
            'user_max_amount' => (int)$this->user_max_amount,
            'is_day_account' => (boolean)$this->is_day_account,
            'type' => $this->type,
            'min_money' => (float)$this->min_money,
            'is_number_buy' => (bool)$this->is_number_buy,
            'attrs' => $this->getAttrData(),
            'can_buy' => data_get($this, 'can_buy', true),

            'user_amount' => $this->whenLoaded('userBuys', $this->userBuys->sum('amount')),//用户投资金额
            'all_user_amount' => $this->whenLoaded('allUserBuys', $this->allUserBuys->sum('amount')),//用户投资金额
            'all_amount' => (float)$this->all_amount,//总需投资金额

        ];
    }
}
