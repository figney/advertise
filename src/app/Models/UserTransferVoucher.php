<?php

namespace App\Models;

use App\Models\Traits\AdminDataScope;
use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserTransferVoucher extends Model
{
    use HasDateTimeFormatter,AdminDataScope;

    protected $table = 'user_transfer_vouchers';
    protected $guarded = [];

    protected $casts = [
        'status' => 'bool',
    ];

    public function channelItem()
    {
        return $this->belongsTo(RechargeChannelList::class, 'channel_item_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function userRechargeOrder()
    {
        return $this->belongsTo(UserRechargeOrder::class, 'order_id');
    }

}
