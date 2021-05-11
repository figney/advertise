<?php


namespace App\Models;


use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

class TransferVoucher extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";

    protected $guarded = [];

}
