<?php

namespace App\Admin\Actions\Grid;

use App\Models\UserWithdrawOrder;
use App\Services\WithdrawService;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class UserWithdrawCheckDataUpdate extends RowAction
{

    protected $title = "<span class='btn btn-sm btn-info margin-right-xs'>检测数据</span>";


    public function handle(Request $request)
    {
        $userWithdrawOrder = UserWithdrawOrder::query()->find($this->getKey());

        WithdrawService::make()->checkData($userWithdrawOrder);

        return $this->response()->refresh();
    }

}
