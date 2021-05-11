<?php


namespace App\Services\Pay;


use App\Enums\OrderStatusType;
use App\Enums\WalletLogType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;
use App\Services\RechargeService;
use App\Services\WithdrawService;
use Carbon\Carbon;

class JstPayService extends BaseService
{
    protected string $host = "https://send.jst.rocks";

    protected string $uid = "pmsua";

    protected string $key = "LLErvJ8Hx5xD3nUS";


    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url)
    {

        $notifyUrl = config('app.url') . route('jstPayInBack', [], false);

        $data['uid'] = $this->uid;
        $data['orderid'] = $userRechargeOrder->order_sn;
        $data['channel'] = $rechargeChannelList->bank_code;
        $data['notify_url'] = $notifyUrl;
        $data['return_url'] = $redirect_url;
        $data['amount'] = $userRechargeOrder->amount;
        $data['userip'] = $this->getIP();
        $data['timestamp'] = Carbon::now()->timestamp;
        $data['custom'] = 'pay';
        $data['sign'] = $this->sign($data);

        $res = \Http::asForm()->post("{$this->host}/pay", $data);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $res_data = $res->json();

        $status = (int)data_get($res_data, "status");
        if ($status !== 10000) {
            $userRechargeOrder->delete();

            abort(400, $status);
        }

        return data_get($res_data, "result.payurl");

    }

    public function payInBack($data)
    {
        $result = json_decode(data_get($data, 'result'));

        $order_sn = data_get($result, 'orderid');
        $amount = (float)data_get($result, 'amount', 0);
        $sign = data_get($data, 'sign');
        $platform_sn = data_get($result, 'transactionid');
        $status = (int)data_get($data, "status");
        $payStatus = $status == 10000;
        if ($status !== 10000) {
            \Log::error("JST支付回调状态错误：" . $status);
            //abort(400, $status);
        }

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");
        if ($order->order_status == OrderStatusType::PaySuccess) return;
        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");

        $data_sign = $this->sign($data);

        if ($data_sign === $sign) {
            $order->back_time = now();
            $order->platform_sn = $platform_sn;
            $order->actual_amount = (float)$amount;
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$amount;
            if ($payStatus) {

                RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {
                $message = $status;
                $order->remark = $message;
                RechargeService::make()->rechargeOrderError($order);
            }
        } else {
            abort(400, "Bad Signature");
        }

    }

    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        $notifyUrl = config('app.url') . route('jstPayOutBack', [], false);

        $data['uid'] = $this->uid;
        $data['orderid'] = $userWithdrawOrder->order_sn;
        $data['channel'] = 712;
        $data['notify_url'] = $notifyUrl;
        $data['amount'] = (float)$userWithdrawOrder->actual_amount;
        $data['userip'] = $this->getIP();
        $data['timestamp'] = Carbon::now()->timestamp;
        $data['custom'] = 'payout';
        $data['bank_id'] = $userWithdrawOrder->withdrawChannelItem->bank_code;
        $data['bank_account'] = data_get($userWithdrawOrder->input_data, "bank_account");
        $data['bank_no'] = data_get($userWithdrawOrder->input_data, "bank_no");
        $data['sign'] = $this->sign($data);

        $res = \Http::asForm()->post("{$this->host}/applyfor", $data);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $res_data = $res->json();

        $status = (int)data_get($res_data, "status");

        abort_if($status !== 10000, 400, $status);

        $userWithdrawOrder->platform_sn = data_get($res_data, "result.transactionid");
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    public function payOutBack($data)
    {
        $result = json_decode(data_get($data, 'result'));

        $order_sn = data_get($result, 'orderid');
        $amount = (float)data_get($data, 'result.real_amount', 0);
        $sign = data_get($data, 'sign');
        $platform_sn = data_get($result, 'transactionid');
        $status = (int)data_get($data, "status");
        $payStatus = $status == 10000;
        if ($status !== 10000) {
            \Log::error("JST代付回调状态错误：" . $status);
            //abort(400, $status);
        }

        abort_if(!$order_sn, 400, "order_sn error");

        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");

        if ($userWithdrawOrder->order_status == WithdrawOrderStatusType::CheckSuccess) return;

        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;

        $data_sign = $this->sign($data);

        if ($data_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if ($payStatus) {
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->platform_sn = $platform_sn;
                $userWithdrawOrder->remark = "pay error";
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

        return strtoupper(md5($signPars));//strtolower 小写  strtoupper大写

    }

}
