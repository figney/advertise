<?php


namespace App\Admin\Forms;


use App\Models\LanguageConfig;
use App\Models\UserWithdrawOrder;
use App\Services\WithdrawService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class WithdrawOrderRejectForm extends Form implements LazyRenderable
{
    use LazyWidget;

    public function handle(array $input)
    {
        $userWithdrawOrder = UserWithdrawOrder::query()->find($this->payload['id']);

        $refund = ((int)data_get($input, 'refund')) === 1;
        $remark_slug = data_get($input, 'remark_slug');


        if (!$refund) {
            $userWithdrawOrder->remark_slug = $remark_slug;
            //拒绝订单
            WithdrawService::make()->rejectWithdrawOrder($userWithdrawOrder);

        } else {
            abort_if(\Admin::user()->cannot('user-refund'), 400, "无权限操作");
            $userWithdrawOrder->remark_slug = $remark_slug;
            //拒绝订单
            $userWithdrawOrder = WithdrawService::make()->rejectWithdrawOrder($userWithdrawOrder);
            //退款操作
            WithdrawService::make()->refundWithdrawOrder($userWithdrawOrder);
        }
        //发送通知
        return $this->response()->success("操作成功")->refresh();

    }


    public function form()
    {

        $this->radio('refund', '是否退款')->options([0 => '直接拒绝', 1 => '拒绝并退款'])->default(0);

        $this->select('remark_slug', '驳回理由')->options(LanguageConfig::query()->where('group', '提现失败驳回理由')->pluck('name', 'slug'))->required();

    }
}
