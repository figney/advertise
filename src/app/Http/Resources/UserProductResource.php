<?php

namespace App\Http\Resources;

use App\Models\UserProduct;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /**@var JsonResource| UserProduct $this */
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_type' => $this->product_type,
            'day_cycle' => $this->day_cycle,
            'day_rate' => (float)$this->day_rate,
            'last_grant_time' => $this->last_grant_time,
            'amount' => (float)$this->amount,
            'over_day' => $this->over_day,
            'count_down' => $this->is_over ? 0 : Carbon::make($this->over_day)->floatDiffInSeconds(now()),
            'is_over' => (bool)$this->is_over,
            'is_day_account' => (bool)$this->is_day_account,
            'status' => (bool)$this->status,
            'over_time' => $this->over_time,
            'interest' => (float)$this->interest,
            'interest_count' => (int)$this->interest_count,
            'min_money' => (float)($this->min_money > 0 ? $this->min_money : $this->product->min_money),
            'is_number_buy' => (bool)$this->product->is_number_buy,
            'attrs' => $this->product->getAttrData(),

            'product_title' => LocalDataGet($this->product?->title),

            'day_date' => $this->dayData()

        ];
    }
}
