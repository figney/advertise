<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class UserTransferVoucherCheckLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_transfer_voucher_check_logs';
    protected $guarded = [];
    protected $connection = "mongodb";
}
