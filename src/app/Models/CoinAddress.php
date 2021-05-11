<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class CoinAddress extends Model
{
    use HasDateTimeFormatter, Cachable;

    protected $table = 'recharge_coin_address';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
