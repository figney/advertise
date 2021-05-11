<?php

namespace App\Models;

use App\Enums\WalletType;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class WalletLogMongo extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";

    protected $table = 'wallet_logs';

    protected $guarded = [];

    public function mate()
    {
        return $this->hasOne(WalletLogMate::class, 'wallet_log_id', 'wallet_log_id');
    }

    public function  getShowFeeAttribute()
    {
        if ($this->wallet_type === WalletType::usdt) {
            return round($this->fee, (int)Setting('usdt_decimal'));
        } else {
            return round($this->fee, (int)Setting('money_decimal'));
        }
    }


    public function  showFee()
    {
        if ($this->wallet_type === WalletType::usdt) {
            return round($this->fee, (int)Setting('usdt_decimal'));
        } else {
            return round($this->fee, (int)Setting('money_decimal'));
        }
    }

}
