<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class WalletLog extends Model
{
    use HasDateTimeFormatter,HybridRelations;

    protected $connection = "mysql";

    protected $table = 'wallet_log';

    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function walletCount()
    {
        return $this->belongsTo(WalletCount::class, 'user_id', 'user_id');
    }

    public function mate()
    {
        return $this->hasOne(WalletLogMate::class,'wallet_log_id');
    }
}
