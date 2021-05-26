<?php


namespace App\Services\Pay;


use App\Enums\OrderStatusType;
use App\Enums\WalletLogType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;
use App\Services\RechargeService;
use App\Services\WithdrawService;

class PayPlusService extends BaseService
{
    protected string $appid;
    protected string $key;
    protected string $countryCode;

    protected string $payInHost = "http://api.letspayfast.com/apipay";
    protected string $payOurHost = "http://api.letspayfast.com/apitrans";

    public function withConfig(RechargeChannel $rechargeChannel)
    {

        $this->appid = $rechargeChannel->configValue('appid');
        $this->key = $rechargeChannel->configValue('key');

        return $this;

    }


    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {
        $notifyUrl = config('app.url') . route('PayPlusInBack', [], false);


        $faker = $this->faker();


        $data['bankcode'] = 'all';
        $data['mchId'] = $this->appid;
        $data['orderNo'] = $userRechargeOrder->order_sn;
        $data['amount'] = $userRechargeOrder->amount;
        $data['goods'] = "email:abc@mail.com/name:ants/phone:" . rand(1111111111, 9999999999);
        $data['product'] = $rechargeChannelList->bank_code;
        $data['notifyUrl'] = $notifyUrl;
        $data['returnUrl'] = $redirect_url;
        $data['sign'] = $this->sign($data);

        $send = array(
            'bankcode' => $data['bankcode'],
            'mchId' => $data['mchId'],
            'orderNo' => $data['orderNo'],
            'amount' => $data['amount'],
            'goods' => $data['goods'],
            'product' => $data['product'],
            'notifyUrl' => $data['notifyUrl'],
            'returnUrl' => $data['returnUrl'],
            'sign' => $data['sign']
        );


        $res = \Http::asForm()->post("{$this->payInHost}", $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $res_data = $res->json();


        $status = data_get($res_data, "retCode") == "SUCCESS";
        $message = data_get($res_data, "retMsg");
        if (!$status) {
            $userRechargeOrder->delete();
            abort(400, $message);
        }

        $userRechargeOrder->platform_sn = data_get($res_data, "platOrder");
        return data_get($res_data, "payUrl");

    }

    public function payInBack($data)
    {
        $order_sn = data_get($data, 'orderNo');
        $amount = (float)data_get($data, 'amount', 0);
        $sign = data_get($data, 'sign');
        $payStatus = data_get($data, 'status') == "2";

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");

        if ($order->order_status == OrderStatusType::PaySuccess) return;

        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");
        $rechargeChannel = $order->rechargeChannel;
        $this->appid = $rechargeChannel->configValue('appid');
        $this->key = $rechargeChannel->configValue('key');

        $signData['mchId'] = data_get($data, 'mchId');
        $signData['orderNo'] = data_get($data, 'orderNo');
        $signData['amount'] = data_get($data, 'amount');
        $signData['product'] = data_get($data, 'product');
        $signData['paySuccTime'] = data_get($data, 'paySuccTime');
        $signData['status'] = data_get($data, 'status');
        $data_sign = $this->sign($signData);

        if ($data_sign === $sign) {
            $order->back_time = now();
            $order->actual_amount = (float)$amount;
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$amount;
            if ($payStatus) {
                $message = "pay success";
                $order->remark = $message;
                RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {
                $message = '1：支付中 5：失效 (' . data_get($data, 'status') . ')';
                $order->remark = $message;
                RechargeService::make()->rechargeOrderError($order);
            }

        } else {
            abort(400, "Bad Signature");
        }

    }

    /**
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     */
    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        $this->appid = $withdrawChannel->configValue('appid');
        $this->key = $withdrawChannel->configValue('key');

        $faker = $this->faker();


        $data['type'] = 'api';
        $data['mchId'] = $this->appid;
        $data['mchTransNo'] = $userWithdrawOrder->order_sn;
        $data['amount'] = (float)$userWithdrawOrder->actual_amount;
        $data['accountNo'] = data_get($userWithdrawOrder->input_data, "acc_no");
        $data['accountName'] = data_get($userWithdrawOrder->input_data, "acc_name");
        $data['remarkInfo'] = "email:abc@mail.com/phone:" . rand(1111111111, 9999999999);
        $data['bankCode'] = $userWithdrawOrder->withdrawChannelItem->bank_code;
        $data['notifyUrl'] = config('app.url') . route('payPlusOutBack', [], false);
        $data['sign'] = $this->sign($data);


        $send = array(
            'type' => $data['type'],
            'mchId' => $data['mchId'],
            'mchTransNo' => $data['mchTransNo'],
            'amount' => $data['amount'],
            'accountNo' => $data['accountNo'],
            'accountName' => $data['accountName'],
            'remarkInfo' => $data['remarkInfo'],
            'bankCode' => $data['bankCode'],
            'notifyUrl' => $data['notifyUrl'],
            'sign' => $data['sign']
        );


        $res = \Http::asForm()->post("{$this->payOurHost}", $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $re_data = $res->json();

        $status = data_get($re_data, "retCode") == "SUCCESS";
        $message = data_get($re_data, "retMsg");
        abort_if(!$status, 400, $message);

        $userWithdrawOrder->platform_sn = data_get($re_data, "platOrder");
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    //没有主动回调  此函数不再使用
    public function payOutBack($data)
    {
        $order_sn = data_get($data, 'mchTransNo');

        $sign = data_get($data, 'sign');

        $payStatus = data_get($data, 'status') == "2";


        abort_if(!$order_sn, 400, "order_sn error");
        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");

        if ($userWithdrawOrder->order_status == WithdrawOrderStatusType::CheckSuccess) return;

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;

        $this->appid = $channel->configValue('appid');
        $this->key = $channel->configValue('key');

        $signData['mchId'] = data_get($data, 'mchId');
        $signData['mchTransNo'] = data_get($data, 'mchTransNo');
        $signData['amount'] = data_get($data, 'amount');
        $signData['status'] = data_get($data, 'status');
        $signData['transSuccTime'] = data_get($data, 'transSuccTime');
        $data_sign = $this->sign($signData);

        if ($data_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if ($payStatus) {
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->remark = '1：处理中 3：失败 (' . data_get($data, 'status') . ')';
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
            }

        } else {
            abort(400, "Bad Signature");
        }

    }

    /**
     * 代付订单查询
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     */
    public function withdrawalResult(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "订单状态不支持");

        $this->appid = $withdrawChannel->configValue('appid');
        $this->key = $withdrawChannel->configValue('key');

        $params['mchId'] = $this->appid;
        $params['mchTransNo'] = $userWithdrawOrder->order_sn;
        $params['sign'] = $this->sign($params);

        $res = \Http::post("http://api.letspayfast.com/qtransorder", $params);

        abort_if($res->clientError(), $res->status(), "请求失败");
        $re_data = $res->json();

        $transStatus = data_get($re_data, "code");
        switch ($transStatus) {
            case 0:
                abort(200, '订单处理中');
                break;
            case 1:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($res, 'msg', "success");
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
                break;
            case -1:
                $userWithdrawOrder->back_time = now();
                $userWithdrawOrder->remark = data_get($res, 'msg');
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
                break;
        }


    }

    public function withConfigWithdraw(WithdrawChannel $withdrawChannel)
    {
        $this->appid = $withdrawChannel->configValue('appid');
        $this->key = $withdrawChannel->configValue('key');

        return $this;
    }

    public function withdraw_bank_list()
    {
        $host = "{$this->payInHost}/api/open/nigeria/banks";

        $send['version'] = '1.0';
        $send['appid'] = $this->appid;
        $send['ts'] = time() . '';
        $send['signInfo'] = $this->sign($send);

        $res = \Http::post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "code");
        $message = data_get($data, "msg");

        abort_if($status, 400, $message);
        return data_get($data, "data");
    }

    private function sign($data): string
    {
        $signPars = "";
        ksort($data);
        foreach ($data as $k => $v) {
            if ("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }

        $signPars .= 'key=' . $this->key;

        return strtoupper(md5($signPars));
        //return strtolower(sha256($signPars));//strtolower 小写  strtoupper大写

    }
}
