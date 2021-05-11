<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserWalletResource extends JsonResource
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
            'balance' => (float)$this->balance,
            'usdt_balance' => (float)$this->usdt_balance,
            'give_balance' => (float)$this->give_balance,
            'wallet_count' => $this->whenLoaded('walletCount', UserWalletCountResource::make($this->walletCount))
        ];
    }
}
