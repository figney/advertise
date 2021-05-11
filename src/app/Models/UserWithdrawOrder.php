<?php

namespace App\Models;

use App\Enums\WithdrawOrderStatusType;
use App\Models\Traits\AdminDataScope;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class UserWithdrawOrder extends Model
{
    use HasDateTimeFormatter, HybridRelations,AdminDataScope;

    protected $table = 'user_withdraw_orders';

    protected $guarded = [];

    protected $casts = [
        'input_data' => 'json',
        'is_pay' => 'bool',
    ];


    public function user()
    {
        return $this->belongsTo(User::class)->with(['invite', 'wallet', 'walletCount']);
    }

    public function withdrawChannel()
    {
        return $this->belongsTo(WithdrawChannel::class, 'withdraw_channel_id');

    }

    public function withdrawChannelItem()
    {
        return $this->belongsTo(WithdrawChannelList::class, 'withdraw_channel_item_id');

    }

    public function checkData()
    {
        return $this->hasOne(UserWithdrawOrderCheckData::class);
    }

    public function remarkContent($local = null)
    {
        if ($this->remark_slug) {
            return Lang(Str::upper($this->remark_slug), [], $local);
        }
        return $this->remark;
    }

    public function scopePay($query)
    {
        return $query->where('order_status', WithdrawOrderStatusType::CheckSuccess);
    }
}
