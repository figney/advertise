<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletCount extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'wallet_count';
    protected $guarded = [];


}
