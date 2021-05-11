<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeChannelList extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mysql";
    protected $table = "recharge_channel_list";
    protected $guarded = [];

    protected $casts = [
        'son_bank_list' => 'json'
    ];

    /**
     * @return BelongsTo
     */
    public function rechargeChannel(): BelongsTo
    {
        return $this->belongsTo(RechargeChannel::class);
    }


    protected static function booted()
    {

        static::created(function (RechargeChannelList $rechargeChannelItem) {
            $rechargeChannelItem->rechargeChannel->updateMinAdnMaxMoney();
        });

        static::updated(function (RechargeChannelList $rechargeChannelItem) {
            $rechargeChannelItem->rechargeChannel->updateMinAdnMaxMoney();
        });
    }

}
