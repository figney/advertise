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
use Faker\Factory;

class FPayTHBService extends BaseService
{

    private string $host = "https://liveapi.fpay.support/";
    //private string $host = "https://sandboxapi.fpay.support/";

    private string $api_key;
    private string $secret_key;
    private string $username;

    public function withConfig(RechargeChannel $rechargeChannel)
    {

        $this->api_key = $rechargeChannel->configValue('api_key');
        $this->secret_key = $rechargeChannel->configValue('secret_key');
        $this->username = $rechargeChannel->configValue('username');

        return $this;

    }

    public function withConfigWithdraw(WithdrawChannel $withdrawChannel)
    {

        $this->api_key = $withdrawChannel->configValue('api_key');
        $this->secret_key = $withdrawChannel->configValue('secret_key');
        $this->username = $withdrawChannel->configValue('username');
        return $this;
    }

    public function callback($data)
    {
        $order_sn = data_get($data, "order_id");
        $order_status = data_get($data, "order_status");
        $type = data_get($data, "type");
        $token = data_get($data, "token");
        $amount = data_get($data, "amount");
        $remarks = data_get($data, "remarks");
        abort_if(!$order_sn, 400, "order_sn error");

        //充值订单回调
        if ($type === "deposit") {
            $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
            abort_if(!$order, 400, "The order does not exist");
            abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");
            $rechargeChannel = $order->rechargeChannel;
            $this->api_key = $rechargeChannel->configValue('api_key');
            $this->secret_key = $rechargeChannel->configValue('secret_key');
            $this->username = $rechargeChannel->configValue('username');
            $sign = md5($this->secret_key . $order_sn);
            //签名验证通过
            if ($sign === $token) {
                $order->back_time = now();
                $order->actual_amount = (float)$amount;
                //修改金额，防止用户不按提交金额付款
                $order->amount = (float)$amount;
                if ($order_status === "completed") {
                    RechargeService::make()->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
                } else {
                    $order->remark = $remarks;
                    RechargeService::make()->rechargeOrderError($order);
                }
            } else {
                abort(400, "Bad Signature");
            }
        }

        //提现回调
        if ($type === "withdrawal") {
            $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
            abort_if(!$userWithdrawOrder, 400, "The order does not exist");
            abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");
            $channel = $userWithdrawOrder->withdrawChannel;
            $this->api_key = $channel->configValue('api_key');
            $this->secret_key = $channel->configValue('secret_key');
            $this->username = $channel->configValue('username');
            $sign = md5($this->secret_key . $order_sn);
            //签名验证通过
            if ($sign === $token) {
                $userWithdrawOrder->back_time = now();
                if ($order_status === "completed") {
                    WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
                } else {
                    $userWithdrawOrder->remark = $remarks;
                    WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
                }

            } else {
                abort(400, "Bad Signature");
            }
        }

    }

    public function payOut(UserWithdrawOrder $userWithdrawOrder)
    {

        $auth = $this->auth();
        $send = array(
            'auth' => $auth,
            'amount' => $userWithdrawOrder->actual_amount,
            'currency' => Setting('fiat_code'),
            'orderid' => $userWithdrawOrder->order_sn,
            'bank_id' => $userWithdrawOrder->withdrawChannelItem->bank_code,
            'bank_branch' => "",
            'holder_name' => data_get($userWithdrawOrder->input_data, "holder_name"),
            'account_no' => data_get($userWithdrawOrder->input_data, "account_no"),
        );
        $host = "{$this->host}merchant/withdraw_orders";
        $res = \Http::asForm()->post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, $message);

        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();
    }

    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, RechargeChannelList $rechargeChannelList, string $redirect_url)
    {
        abort_if(!$rechargeChannelList, 400, Lang('参数错误'));

        $auth = $this->auth();

        $faker = $this->faker();

        $bank_id = $rechargeChannelList->bank_code;

        $send = array(
            'username' => $this->username,
            'auth' => $auth,
            'amount' => $userRechargeOrder->amount,
            'currency' => Setting('fiat_code'),
            'orderid' => $userRechargeOrder->order_sn,
            'email' => $faker->freeEmail,
            'phone_number' => $faker->phoneNumber,
            'bank_id' => $bank_id,
            'redirect_url' => $redirect_url
        );

        $host = "{$this->host}merchant/generate_orders";

        $res = \Http::asForm()->post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, $message);
        return data_get($data, "p_url");

    }


    public function currency()
    {
        $host = "{$this->host}merchant/currency";

        $res = \Http::asForm()->post($host);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, $message);
        return data_get($data, "rate");
    }

    /**
     * 认证码接口
     * @return string
     */
    public function auth()
    {
        $host = "{$this->host}merchant/auth";

        $send = array('username' => $this->username, 'api_key' => $this->api_key);

        $res = \Http::asForm()->post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, "auth error");
        return data_get($data, "auth");
    }

    public function bank_list()
    {
        $host = "{$this->host}wallet/bank_list";
        $send = array('username' => $this->username);
        $res = \Http::asForm()->post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, $message);
        return data_get($data, "data");
    }

    public function withdraw_bank_list()
    {
        $host = "{$this->host}wallet/withdraw_bank_list";
        $send = array('username' => $this->username, 'currency' => Setting('fiat_code'));
        $res = \Http::asForm()->post($host, $send);
        abort_if($res->clientError(), $res->status(), "请求失败");
        $data = $res->json();
        $status = data_get($data, "status");
        $message = data_get($data, "message");
        abort_if(!$status, 400, $message);
        return data_get($data, "data");
    }
}
