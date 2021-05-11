<?php


namespace App\Admin\Controllers;


use App\Enums\WalletLogType;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class DataCountController extends AdminController
{

    public function walletLogCount(Content $content)
    {


       $type =  WalletLogType::asArray();




        return $content;

    }

}
