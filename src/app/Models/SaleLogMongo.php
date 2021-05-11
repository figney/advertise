<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;


class SaleLogMongo extends Model
{
    use HasFactory;

    protected $table = "x_sale_logs";

    protected $connection = "mongodb";

    protected $guarded = [];
}
