<?php

namespace App\Services\Pay;

use App\Enums\OrderStatusType;
use App\Enums\WalletLogType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\RechargeChannel;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;
use App\Services\RechargeService;
use App\Services\WithdrawService;
use Illuminate\Support\Str;

class IPayIndianService extends BaseService
{
    protected string $keySecret;
    protected string $key_id;

    /**
     * @param User $user
     * @param UserRechargeOrder $userRechargeOrder
     * @param RechargeChannel $rechargeChannel
     * @param string $redirect_url
     * @return array|mixed
     */
    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, RechargeChannel $rechargeChannel, string $redirect_url)
    {
        $this->keySecret = $rechargeChannel->configValue('key_secret');
        $this->key_id = $rechargeChannel->configValue('key_id');

        $data = [//组成array
            'userNo' => strval($userRechargeOrder->user_id),
            'orderId' => strval($userRechargeOrder->order_sn),
            'amount' => doubleval($userRechargeOrder->amount),
        ];
        $sign = $this->generateSign($data);
        $data['sign'] = $sign;

        $res = \Http::withHeaders([
            'Content-Type' => 'application/json;charset=utf-8',
            'keyId' => $this->key_id
        ])->post("https://api.ipaytmindian.com/v1/pay", $data)->object();

        $result = (int)data_get($res, "result", 0);
        abort_if($result !== 2, 400, data_get($res, "msg", "error"));
        return data_get($res, "data");

    }

    /**
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     */
    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        $this->keySecret = $withdrawChannel->configValue('key_secret');
        $this->key_id = $withdrawChannel->configValue('key_id');
        $bank_code = Str::upper($userWithdrawOrder->withdrawChannelItem->bank_code);
        $params['userNo'] = strval($userWithdrawOrder->user_id);
        $params['orderId'] = strval($userWithdrawOrder->order_sn);
        $params['amount'] = intval($userWithdrawOrder->actual_amount);
        if ($bank_code == "UPI") {
            $params['vpa'] = data_get($userWithdrawOrder->input_data, "vpa");
        }
        if ($bank_code == "BANK") {
            $params['accountName'] = data_get($userWithdrawOrder->input_data, "accountName");
            $params['accountNo'] = data_get($userWithdrawOrder->input_data, "accountNo");
            $params['ifsc'] = data_get($userWithdrawOrder->input_data, "ifsc");
        }
        $sign = $this->generateSign($params);
        $params['sign'] = $sign;
        $url = "https://api.ipaytmindian.com/v1/withdrawal";
        $data = \Http::withHeaders([
            'Content-Type' => 'application/json;charset=utf-8',
            'keyId' => $this->key_id
        ])->post($url, $params)->object();
        $result = (int)data_get($data, "result");
        abort_if($result !== 1, 400, data_get($data, "msg"));
        $platform_osn = data_get($data, "data");
        $userWithdrawOrder->platform_sn = $platform_osn;
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    /**
     * 代付订单查询
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     */
    public function withdrawalResult(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "订单状态不支持");

        $this->keySecret = $withdrawChannel->configValue('key_secret');
        $this->key_id = $withdrawChannel->configValue('key_id');
        $params['orderId'] = $userWithdrawOrder->order_sn;
        $sign = $this->generateSign($params);
        $params['sign'] = $sign;
        $url = "https://api.ipaytmindian.com/v1/withdrawalResult";
        $data = \Http::withHeaders([
            'Content-Type' => 'application/json;charset=utf-8',
            'keyId' => $this->key_id
        ])->post($url, $params)->object();


        $status = (int)data_get($data, "data.status");

        switch ($status) {
            case 1:
                abort(200, '订单处理中');
                break;
            case 2:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($data, 'data.desc', "success");
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
                break;
            case 3:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($data, 'data.desc');
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
                break;
        }


    }

    public function payOutBack($data)
    {
        $order_sn = $i_data['orderNo'] = data_get($data, 'orderNo');
        $payStatus = $i_data['status'] = data_get($data, 'status');
        $desc = $i_data['desc'] = data_get($data, 'desc');
        $i_sign = data_get($data, 'sign');

        abort_if(!$order_sn, 400, "order_sn error");
        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");
        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;
        $this->keySecret = $channel->configValue('key_secret');
        $this->key_id = $channel->configValue('key_id');

        $sign = $this->generateSign($i_data);

        if ($i_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if (intval($payStatus) === 2) {//打款成功
                $userWithdrawOrder->remark = $desc;
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->remark = $desc;
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
            }
        } else {
            abort(400, "Bad Signature");
        }

    }

    public function payInBack($data)
    {
        $order_sn = $i_data['orderNo'] = data_get($data, 'orderNo');
        $amount = $i_data['amount'] = data_get($data, 'amount');
        $payStatus = $i_data['payStatus'] = data_get($data, 'payStatus');
        $i_data['patTime'] = data_get($data, 'patTime');
        $i_sign = data_get($data, 'sign');

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");
        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");

        $rechargeChannel = $order->rechargeChannel;
        $this->keySecret = $rechargeChannel->configValue('key_secret');
        $this->key_id = $rechargeChannel->configValue('key_id');

        $sign = $this->generateSign($i_data);

        if ($i_sign === $sign) {
            $order->back_time = now();
            $order->actual_amount = (float)$amount;
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$amount;

            if ((int)$payStatus === 1) {
                $message = data_get($data, "message", "pay success");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {
                $message = data_get($data, "message", "pay error");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderError($order);
            }
        } else {
            abort(400, "Bad Signature");
        }


    }


    function generateSign($data)
    {
        if (empty($data)) {
            abort(400, "SIGN ERROR");
        }
        ksort($data);//进行升序
        $dataStr = "";
        foreach ($data as $val) {
            $dataStr .= $val;
        }
        return md5($dataStr . $this->keySecret);
    }
}
