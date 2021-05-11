<?php

namespace App\Http\Resources;

use App\Models\WalletLog;
use App\Models\WalletLogMongo;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class WalletLogResource extends JsonResource
{

    public function toArray($request)
    {
        /** @var WalletLogMongo|WalletLog $this */

        return [
            'id' => $this->id,
            'wallet_type' => Lang(Str::upper($this->wallet_type)),
            'wallet_slug' => Lang(Str::upper($this->wallet_slug)),
            'action_type' => Lang(Str::upper($this->action_type)),
            'fee' => $this->show_fee,
            'created_at' => TimeFormat($this->created_at),
        ];
    }
}
