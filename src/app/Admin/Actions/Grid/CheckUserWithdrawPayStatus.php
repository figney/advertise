<?php

namespace App\Admin\Actions\Grid;

use App\Enums\WithdrawOrderStatusType;
use App\Models\UserWithdrawOrder;
use App\Services\WithdrawService;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class CheckUserWithdrawPayStatus extends RowAction
{

    protected $title = "<button class='btn btn-info btn-sm'>支付查询</button>";


    public function handle(Request $request)
    {

        try {
            $userWithdrawOrder = UserWithdrawOrder::query()->find($this->getKey());
            abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, '当前订单状态无法操作');
            WithdrawService::make()->checkStatusWithdrawOrder($userWithdrawOrder);
            return $this->response()->success("操作成功")->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage())->alert();
        }


    }

}
