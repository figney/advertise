<?php


namespace App\Services\Pay;


use App\Enums\WithdrawOrderStatusType;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;
use App\Services\WithdrawService;

class PaytmCashService extends BaseService
{

    public function withdrawalResult(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "订单状态不支持");

        $merchant_sn = $withdrawChannel->configValue('merchant_sn');
        $merchant_ak = $withdrawChannel->configValue('merchant_ak');
        $merchant_sk = $withdrawChannel->configValue('merchant_sk');
        $host = $withdrawChannel->configValue('host');
        $order_sn = $userWithdrawOrder->order_sn;

        $params = array(
            'merchant_sn' => $merchant_sn,
            'order_sn' => $order_sn,
            'sign' => md5($merchant_ak . $order_sn . $merchant_sk)
        );

        $url = "{$host}/gateway/payoutcheck/"; // Gateway
        $data = \Http::post($url, $params)->object();
        $status = (int)data_get($data, "data.status");

        switch ($status) {
            case 0:
                abort(200, '订单处理中');
                break;
            case 1:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($data, 'data.description', "success");
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
                break;
            case 9:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($data, 'data.description');
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
                break;
        }
    }

}
