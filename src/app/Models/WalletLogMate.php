<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class WalletLogMate extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";

    protected $table = 'wallet_log_mate';

    protected $guarded = [];


}
