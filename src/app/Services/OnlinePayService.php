<?php


namespace App\Services;

use App\Enums\NextActionType;
use App\Enums\OrderStatusType;
use App\Enums\PlatformType;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\CoinAddress;
use App\Models\UserWithdrawOrder;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Models\WithdrawChannel;
use Faker\Factory;
use App\Models\RechargeChannel;
use App\Models\User;
use App\Models\UserRechargeOrder;

class OnlinePayService extends BaseService
{
    protected RechargeService $rechargeService;
    protected WithdrawService $withdrawService;

    public function __construct()
    {
        $this->rechargeService = new RechargeService();
        $this->withdrawService = new WithdrawService();
    }

    /**
     * 老孙回调
     * @param $data
     */
    public function laoSunBack($data)
    {
        $tx_type = data_get($data, 'tx_type');
        if ($tx_type === "recharge") {
            $this->laoSunPayInBak($data);
        }
        if ($tx_type === "cash") {
            $this->laoSunPayOutBak($data);
        }

    }

    /**
     * 老孙提币回调
     * @param $data
     */
    public function laoSunPayOutBak($data)
    {

        $tx_type = data_get($data, 'tx_type');
        $order_sn = data_get($data, 'trade_id');

        $status = data_get($data, 'status') == "SUCCESS";

        $platform_sn = (int)data_get($data, 'id');

        abort_if($platform_sn <= 0, 400, '上游订单标识 id 不存在');
        abort_if($tx_type !== "cash", 400, 'tx_type error');

        abort_if(!$order_sn, 400, "order_sn error");

        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        //订单是否存在
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");
        //如果是已支付，直接返回
        if ($userWithdrawOrder->order_status === WithdrawOrderStatusType::CheckSuccess) return;
        //订单状态不允许修改
        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");

        $channel = $userWithdrawOrder->withdrawChannel;
        $api_key = $channel->configValue('api_key');
        $api_key_v2 = $channel->configValue('api_key_v2');
        $merchant_id = $channel->configValue('merchant_id');
        $checkSign = LaoSunService::make()->setConfig($api_key, $api_key_v2, $merchant_id)->checkSign($data);
        abort_if(!$checkSign, 400, "签名错误");

        $userWithdrawOrder->back_time = now();

        if ($status) {
            $userWithdrawOrder->platform_sn = $platform_sn;
            $userWithdrawOrder->remark_slug = "支付成功";
            $this->withdrawService->withdrawOrderSuccess($userWithdrawOrder);
        } else {
            $userWithdrawOrder->platform_sn = $platform_sn;
            $userWithdrawOrder->remark = data_get($data, 'message', "pay error");
            $this->withdrawService->withdrawOrderError($userWithdrawOrder);
        }
    }

    /**
     * 老孙支付回调
     * @param $data
     */
    public function laoSunPayInBak($data)
    {

        $sign = data_get($data, 'sign');
        $tx_type = data_get($data, 'tx_type');
        $merchant_id = data_get($data, 'merchant_id');
        $amount = (float)data_get($data, 'amount');
        $status = data_get($data, 'status') == "SUCCESS";
        $to_account = data_get($data, 'to_account');
        $from_account = data_get($data, 'from_account');
        $currency = data_get($data, 'currency');
        $platform_sn = (int)data_get($data, 'id');

        abort_if($currency !== "USDT.TRC20", 400, "currency error");
        abort_if($platform_sn <= 0, 400, '上游订单标识 id 不存在');
        abort_if($tx_type !== "recharge", 400, 'tx_type error');


        //获取支付渠道
        $rechargeChannel = RechargeChannel::query()->where('slug', PlatformType::LaoSun)->where('status', 1)->first();

        //签名验证
        $payZjrService = LaoSunService::make()->setChannel($rechargeChannel);
        abort_if(!$payZjrService->checkSign($data), 400, 'sign error');

        //获取用户
        $coinAddress = CoinAddress::query()->where('address', $to_account)->first();
        $user = $coinAddress->user;
        abort_if(!$user, 400, '用户不存在');

        //判断订单是否存在
        $order = UserRechargeOrder::query()->where('recharge_channel_id', $rechargeChannel->id)
            ->where('platform_sn', $platform_sn)->first();

        if (!$order) {
            //创建订单
            $order = $this->rechargeService->createRechargeOrder($user, WalletType::usdt, $amount, NextActionType::Wallet, 0, $rechargeChannel, null);
            $order->platform_sn = $platform_sn;
            $order->back_time = now();
            $order->actual_amount = $amount;
            $order->address = $from_account;
        }
        if ($order->order_status == OrderStatusType::PaySuccess) {
            return true;
        }
        //判断订单状态
        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");

        //订单支付成功
        if ($status) {
            $order->remark_slug = "充值成功";
            $this->rechargeService->rechargeOrderSuccess($order, WalletLogType::DepositUSDTRecharge, function (Wallet $wallet, WalletLog $walletLog) use ($amount, $coinAddress) {
                $coinAddress->increment('amount', $amount);
            });
        } else {
            $order->remark_slug = "充值失败";
            $this->rechargeService->rechargeOrderError($order);

        }

    }

    /**
     * paytmCash支付下单
     * @param User $user
     * @param UserRechargeOrder $userRechargeOrder
     * @param RechargeChannel $rechargeChannel
     * @param string $redirect_url
     * @return array|mixed|null
     */
    public function paytmCashPayIn(User $user, UserRechargeOrder $userRechargeOrder, RechargeChannel $rechargeChannel, string $redirect_url)
    {
        $merchant_sn = $rechargeChannel->configValue('merchant_sn');
        $merchant_ak = $rechargeChannel->configValue('merchant_ak');
        $merchant_sk = $rechargeChannel->configValue('merchant_sk');
        $host = $rechargeChannel->configValue('host');


        $order_sn = $userRechargeOrder->order_sn; // Merchant system unique order number
        $amount = $userRechargeOrder->amount;
        $name = $user->name;
        $email = $user->national_number . '@email.com';
        $phone = $user->national_number;


        $faker = Factory::create("en_IN");

        $name = $faker->name;
        $email = $faker->freeEmail;
        $phone = $faker->phoneNumber;

        $remark = 'Pay';

        $params = array(
            'merchant_sn' => $merchant_sn,
            'order_sn' => $order_sn,
            'amount' => $amount,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'remark' => $remark,
            'redirect_url' => $redirect_url,
            'sign' => md5($merchant_ak . $order_sn . $amount . $merchant_sk)
        );


        $url = "{$host}/gateway/payin/"; // Gateway


        $data = \Http::post($url, $params)->object();


        $platform_osn = data_get($data, "data.platform_osn");
        if (!$platform_osn) return null;

        $userRechargeOrder->platform_sn = $platform_osn;
        $userRechargeOrder->save();

        $pay_url = data_get($data, "data.pay_url");

        return $pay_url;


    }

    /**
     * paytmCash付款回调
     * @param $data
     */
    public function paytmCashPayInBack($data)
    {
        $order_sn = data_get($data, 'order_sn');
        abort_if(!$order_sn, 400, "order_sn error");

        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");
        abort_if($order->order_status !== 0, 400, "Order status error");

        $rechargeChannel = $order->rechargeChannel;

        $merchant_sn = $rechargeChannel->configValue('merchant_sn');
        $merchant_ak = $rechargeChannel->configValue('merchant_ak');
        $merchant_sk = $rechargeChannel->configValue('merchant_sk');
        $sign = md5($merchant_ak . $data['platform_osn'] . $data['status'] . $data['amount'] . $merchant_sk);
        if ($sign == $data['sign'] && $merchant_sn == $data['merchant_sn']) {

            $order->back_time = now();
            $order->actual_amount = (float)$data['amount'];
            //修改金额，防止用户不按提交金额付款
            $order->amount = (float)$data['amount'];

            if (intval($data['status']) === 1) {//支付成功
                $message = data_get($data, "message");
                $order->remark = $message;
                $this->rechargeService->rechargeOrderSuccess($order, WalletLogType::DepositOnlinePayRecharge);
            } else {//支付异常
                $message = data_get($data, "message");
                $order->remark = $message;
                $this->rechargeService->rechargeOrderError($order);
            }
        } else {
            abort(400, "Bad Signature");
        }

    }

    /**
     * paytmCash代付请求
     * @param UserWithdrawOrder $userWithdrawOrder
     * @param WithdrawChannel $withdrawChannel
     * @return null
     */
    public function paytmCashPayOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {

        $faker = Factory::create("en_IN");

        $merchant_sn = $withdrawChannel->configValue('merchant_sn');
        $merchant_ak = $withdrawChannel->configValue('merchant_ak');
        $merchant_sk = $withdrawChannel->configValue('merchant_sk');
        $host = $withdrawChannel->configValue('host');
        $order_sn = $userWithdrawOrder->order_sn; // Merchant system unique order number
        $amount = $userWithdrawOrder->actual_amount;
        $type = 'upi';
        $upi_handle = '';
        $account = '';
        $ifsc = '';
        if ($userWithdrawOrder->withdrawChannelItem->bank_code == "upi") {
            $type = 'upi';
            $upi_handle = data_get($userWithdrawOrder->input_data, "upi_handle");
            $account = '';
            $ifsc = '';
        }
        if ($userWithdrawOrder->withdrawChannelItem->bank_code == "bank") {
            $type = 'bank';
            $upi_handle = '';
            $account = data_get($userWithdrawOrder->input_data, "account");
            $ifsc = data_get($userWithdrawOrder->input_data, "ifsc");
        }
        $name = $faker->name;
        $remark = 'Withdraw';
        $params = array(
            'merchant_sn' => $merchant_sn,
            'order_sn' => $order_sn,
            'amount' => $amount,
            'type' => $type,
            'name' => $name,
            'account' => $account,
            'ifsc' => $ifsc,
            'upi_handle' => $upi_handle,
            'remark' => $remark,
            'sign' => md5($merchant_ak . $order_sn . $account . $upi_handle . $amount . $merchant_sk)
        );
        $url = "{$host}/gateway/payout/"; // Gateway
        $data = \Http::post($url, $params)->object();


        $platform_osn = data_get($data, "data.platform_osn");

        abort_if(!$platform_osn, 400, data_get($data, "message", "未知错误，请联系开发人员"));


        $userWithdrawOrder->platform_sn = $platform_osn;
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    /**
     *  paytmCash代付回调
     * @param $data
     */
    public function paytmCashPayOutBack($data)
    {

        $order_sn = data_get($data, 'order_sn');
        abort_if(!$order_sn, 400, "order_sn error");

        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        //订单是否存在
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");
        //如果是已支付，直接返回
        if ($userWithdrawOrder->order_status === OrderStatusType::PaySuccess) return;
        //订单状态不允许修改
        abort_if($userWithdrawOrder->order_status !== OrderStatusType::Paying, 400, "Order status error");

        $merchant_sn = $userWithdrawOrder->configValue('merchant_sn');
        $merchant_ak = $userWithdrawOrder->configValue('merchant_ak');
        $merchant_sk = $userWithdrawOrder->configValue('merchant_sk');

        $sign = md5($merchant_ak . $data['platform_osn'] . $data['status'] . $data['amount'] . $merchant_sk);

        if ($sign == $data['sign'] && $merchant_sn == $data['merchant_sn']) {

            $userWithdrawOrder->back_time = now();

            if (intval($data['status']) === 1) {//打款成功
                $message = data_get($data, "message");
                $userWithdrawOrder->remark = $message;
                $this->withdrawService->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $message = data_get($data, "message");
                $userWithdrawOrder->remark = $message;
                $this->withdrawService->withdrawOrderError($userWithdrawOrder);
            }
        } else {
            abort(400, "Bad Signature");
        }

    }

}
