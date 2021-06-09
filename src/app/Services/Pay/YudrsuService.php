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

class YudrsuService extends BaseService
{
    protected string $mer_no;
    protected string $key;
    protected string $countryCode;

    protected string $payInAddress;
    protected string $payOutAddress;


    public function withConfig(RechargeChannel $rechargeChannel)
    {

        $this->mer_no = $rechargeChannel->configValue('mer_no');
        $this->key = $rechargeChannel->configValue('key');
        $this->countryCode = $rechargeChannel->configValue('countryCode');
        $this->payInAddress = $rechargeChannel->configValue('payInAddress');
        $this->payOutAddress = $rechargeChannel->configValue('payOutAddress');

        return $this;

    }


    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {
        $notifyUrl = config('app.url') . route('yudrsuPayInBack', [], false);


        $faker = $this->faker();


        $data['mer_no'] = $this->mer_no;
        $data['mer_order_no'] = $userRechargeOrder->order_sn;
        $data["pname"] = $user->name;
        $data["pemail"] = $faker->email;
        $data["phone"] = str_replace("+", "", $faker->phoneNumber);
        $data["order_amount"] = $userRechargeOrder->amount;
        $data["countryCode"] = $this->countryCode;
        $data["ccy_no"] = Setting('fiat_code');
        $data["busi_code"] = $rechargeChannelList->bank_code;
        $data["goods"] = "goods";
        $data["notifyUrl"] = $notifyUrl;
        $data["pageUrl"] = $redirect_url;
        $data["bankCode"] = $son_code;
        $data["sign"] = $this->sign($data);


        \Log::debug("Yudrsu payIn request: " . json_encode($data));
        $res = \Http::post("{$this->payInAddress}/ty/orderPay", $data);
        \Log::debug("Yudrsu payIn response: " . $res);
        abort_if($res->clientError(), $res->status(), "The request failed");
        $res_data = $res->json();


        $status = data_get($res_data, "status") == "SUCCESS";
        $message = data_get($res_data, "err_msg");
        if (!$status) {
            //\Log::error("创建Yudrsu支付订单失败：" . $message, ['post_data' => $data, 'res_data' => $res_data]);
            //$userRechargeOrder->order_status = OrderStatusType::Close;
            //$userRechargeOrder->remark = $message;
            $userRechargeOrder->delete();

            abort(400, $message);
        }


        return data_get($res_data, "order_data");

    }

    public function payInBack($data)
    {
        $order_sn = data_get($data, 'mer_order_no');
        $amount = (float)data_get($data, 'pay_amount', 0);
        $sign = data_get($data, 'sign');
        $platform_sn = data_get($data, 'order_no');
        $payStatus = data_get($data, 'status') == "SUCCESS";

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");

        if ($order->order_status == OrderStatusType::PaySuccess) return;

        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");
        $rechargeChannel = $order->rechargeChannel;
        $this->mer_no = $rechargeChannel->configValue('mer_no');
        $this->key = $rechargeChannel->configValue('key');

        $data_sign = $this->sign($data);

        if ($data_sign === $sign) {
            $order->back_time = now();
            $order->platform_sn = $platform_sn;
            $order->actual_amount = (float)$amount;
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$amount;
            if ($payStatus) {
                $message = data_get($data, "err_msg", "pay success");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {
                $message = data_get($data, "err_msg", "pay error");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderError($order);
            }

        } else {
            abort(400, "Bad Signature");
        }

    }

    public function payInCheck(UserRechargeOrder $userRechargeOrder)
    {
        $data['mer_no'] = $this->mer_no;
        $data['mer_order_no'] = $userRechargeOrder->order_sn;
        $data['request_no'] = time();
        $data['request_time'] = now()->format("YmdHis");
        $data["sign"] = $this->sign($data);
    }

    /**
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     */
    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        $this->mer_no = $withdrawChannel->configValue('mer_no');
        $this->key = $withdrawChannel->configValue('key');

        $data['mer_no'] = $this->mer_no;
        $data['mer_order_no'] = $userWithdrawOrder->order_sn;

        $data['acc_no'] = data_get($userWithdrawOrder->input_data, "acc_no");
        $data['acc_name'] = data_get($userWithdrawOrder->input_data, "acc_name");
        $data['ccy_no'] = Setting('fiat_code');
        $data['order_amount'] = (float)$userWithdrawOrder->actual_amount;
        $data['bank_code'] = $userWithdrawOrder->withdrawChannelItem->bank_code;
        $data['notifyUrl'] = config('app.url') . route('yudrsuPayOutBack', [], false);
        $data['summary'] = 'remark';

        $data['sign'] = $this->sign($data);

        \Log::debug("Yudrsu payOut request: " . json_encode($data));
        $res = \Http::post("{$this->payOutAddress}/withdraw/singleOrder", $data);
        \Log::debug("Yudrsu payOut response: " . $res);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $re_data = $res->json();

        $status = data_get($re_data, "status") == "SUCCESS";
        $message = data_get($re_data, "err_msg");
        abort_if(!$status, 400, $message);

        $userWithdrawOrder->platform_sn = data_get($re_data, "order_no");
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    public function payOutBack($data)
    {
        $order_sn = data_get($data, 'mer_order_no');

        $sign = data_get($data, 'sign');

        $payStatus = data_get($data, 'status') == "SUCCESS";


        abort_if(!$order_sn, 400, "order_sn error");
        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");

        if ($userWithdrawOrder->order_status == WithdrawOrderStatusType::CheckSuccess) return;

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;

        $this->mer_no = $channel->configValue('mer_no');
        $this->key = $channel->configValue('key');
        $this->pname = $channel->configValue('pname');

        $data_sign = $this->sign($data);

        if ($data_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if ($payStatus) {
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->remark = data_get($data, "err_msg", "pay error");
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
            }

        } else {
            abort(400, "Bad Signature");
        }

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
        $signPars .= "key=" . $this->key;

        return strtolower(md5($signPars));//strtolower 小写  strtoupper大写

    }
}
