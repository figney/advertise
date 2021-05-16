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

class IvnPayService extends BaseService implements InPay
{

    protected string $host = "https://apipay.ivnpay.cc";
    protected $secret_key = "";
    protected $mch_id = "";
    protected $show_types = "4+40";


    public function withConfig(WithdrawChannel|RechargeChannel $channel)
    {
        $this->secret_key = $channel->configValue('secret_key');

        $this->mch_id = $channel->configValue('mch_id');
        $this->show_types = $channel->configValue('show_types');
        return $this;
    }

    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {
        $params = array(
            "mch_id" => $this->mch_id,
            "mch_uid" => (string)$user->id,
            "mch_order_id" => $userRechargeOrder->order_sn,
            "equipment_type" => 0,
            "expected_mount" => (float)$userRechargeOrder->amount,
            "mch_url" => $redirect_url,
            "show_types" => $this->show_types
        );
        $param = $this->gen_param($params);
        return $this->host . "/public/comm/index.html?param=" . $param;
    }

    public function payInBack($request)
    {

        $param = data_get($request, 'param');
        $data = $this->parse_url_param($param);
        \Log::info("parse_url_param", $data);

        $order_sn = data_get($data, 'mch_order_id');
        $amount = (float)data_get($data, 'amount', 0);
        $sign = data_get($data, 'hash');
        $platform_sn = data_get($data, 'svr_transaction_id');
        $payStatus = data_get($data, 'status') == "1";

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");

        if ($order->order_status == OrderStatusType::PaySuccess) return;

        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");
        $rechargeChannel = $order->rechargeChannel;
        $this->withConfig($rechargeChannel);

        $data_sign = $this->sign($data);
        if ($data_sign === $sign) {
            $order->back_time = now();
            $order->platform_sn = $platform_sn;
            $order->actual_amount = (float)$amount;
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$amount;
            if ($payStatus) {
                $message = data_get($request, "message", "pay success");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {
                $message = data_get($request, "message", "pay error");
                $order->remark = $message;
                RechargeService::make()->rechargeOrderError($order);
            }
        } else {
            abort(400, "Bad Signature");
        }

    }

    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        $this->withConfig($withdrawChannel);

        $params = array(
            "mch_id" => $this->mch_id,
            "mch_uid" => (string)$userWithdrawOrder->user_id,
            "mch_order_id" => $userWithdrawOrder->order_sn,
            "pay_type" => (int)$userWithdrawOrder->withdrawChannelItem->bank_code,
            "amount" => (float)$userWithdrawOrder->actual_amount,
            "notify_url" => config('app.url') . route('ivnPayOutBack', [], false),
            "account" => data_get($userWithdrawOrder->input_data, "account"),
        );

        $params['hash'] = $this->sign($params);

        $res = \Http::post($this->host . "/comm/v1/transfer", $params);


        abort_if($res->clientError(), $res->status(), "请求失败");
        $re_data = $res->json();

        $status = data_get($re_data, "ret") == 0;
        $message = data_get($re_data, "msg");
        abort_if(!$status, 400, $message);

        $userWithdrawOrder->platform_sn = data_get($re_data, "data.svr_transaction_id");
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    public function payOutBack($data)
    {
        $param = data_get($data, 'param');
        $data = $this->parse_url_param($param);
        \Log::info("parse_url_param", $data);

        $order_sn = data_get($data, 'mch_order_id');

        $sign = data_get($data, 'hash');

        $payStatus = data_get($data, 'status') == 1;

        abort_if(!$order_sn, 400, "order_sn error");
        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");

        if ($userWithdrawOrder->order_status == WithdrawOrderStatusType::CheckSuccess) return;

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;

        $this->withConfig($channel);

        $data_sign = $this->sign($data);

        if ($data_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if ($payStatus) {
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->remark = data_get($data, "error_no", "pay error");
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
            }

        } else {
            abort(400, "Bad Signature");
        }

    }

    # 支付页面param参数生成方法
    function gen_param($req_params): string
    {
        $signstr = $this->sign($req_params);
        $req_params['hash'] = $signstr;
        $param_str = json_encode($req_params);
        return base64_encode($param_str);
    }

    function parse_url_param($param)
    {
        $param_str = base64_decode($param);
        parse_str($param_str, $params);
        return $params;
    }

    function sign($req_params): string
    {
        $secret_key = $this->secret_key;
        unset($req_params['hash']);
        ksort($req_params);
        $signstr = '';
        foreach ($req_params as $key => $value) {
            $signstr .= $key . '=' . $value . '&';
        }
        $signstr = rtrim($signstr, '&');
        $signstr .= $secret_key;
        $signstr = md5($signstr);
        $signstr = strtoupper($signstr);
        return $signstr;
    }
}
