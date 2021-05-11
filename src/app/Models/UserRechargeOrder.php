<?php

namespace App\Models;

use App\Enums\OrderStatusType;
use App\Models\Traits\AdminDataScope;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserRechargeOrder extends Model
{
    use HasDateTimeFormatter, AdminDataScope;

    protected $table = 'user_recharge_orders';
    protected $guarded = [];

    protected $casts = [
        'is_pay' => 'bool',
        'next_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rechargeChannel()
    {
        return $this->belongsTo(RechargeChannel::class, 'recharge_channel_id');
    }

    public function rechargeChannelItem()
    {
        return $this->belongsTo(RechargeChannelList::class, 'recharge_channel_item_id');
    }

    public function remarkContent()
    {
        if ($this->remark_slug) {
            return Lang(Str::upper($this->remark_slug));
        }
        return $this->remark;
    }

    public function scopePay($query)
    {
        return $query->where('order_status', OrderStatusType::PaySuccess);
    }


}
