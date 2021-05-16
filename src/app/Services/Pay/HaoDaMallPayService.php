<?php


namespace App\Services\Pay;


use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;

class HaoDaMallPayService extends BaseService implements InPay
{

    protected $mchtId;
    protected $version;
    protected $biz;
    protected $key;


    private $payUrl = "https://pre.pay.haodamall.com/gateway/cashier/mchtPay";

    public function withConfig(WithdrawChannel|RechargeChannel $channel)
    {
        $this->mchtId = $channel->configValue('mchtId');
        $this->version = $channel->configValue('version');
        $this->biz = $channel->configValue('biz');
        $this->key = $channel->configValue('key');

        return $this;
    }

    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {

        $notifyUrl = config('app.url') . route('yudrsuPayInBack', [], false);

        $head = [
            'mchtId' => $this->mchtId,
            'version' => $this->version,
            'biz' => $this->biz,
        ];
        $inputs = [
            'orderId' => $userRechargeOrder->order_sn,
            'orderTime' => $userRechargeOrder->created_at->format("YmdHis"),
            'amount' => $userRechargeOrder->amount * 100,
            'currencyType' => Setting('fiat_code'),
            'goods' => "2201015000659105",//$userRechargeOrder->order_sn,
            'notifyUrl' => $notifyUrl,
            'callBackUrl' => $redirect_url,
            'appId' => $rechargeChannelList->bank_code,
        ];
        $sign = $this->sign($inputs);
        return array_merge($head, $inputs, ['sign' => $sign, 'post_url' => $this->payUrl]);

    }

    public function payInBack($request)
    {

    }

    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {

    }

    public function payOutBack($data)
    {

    }

    /**
     * 生成签名
     * @param array $data
     * @return string $result
     */
    public function sign($data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->toUrlParams($data);

        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数
     */
    public function toUrlParams($data)
    {
        $temp = "";
        foreach ($data as $k => $v) {
            if ($v != "" && !is_array($v)) {
                $temp .= $k . "=" . $v . "&";
            }
        }
        $temp = trim($temp, "&");
        return $temp;
    }

    /*
     *  验签
     * */
    public function checkSign($body, $sign)
    {
        $key = $this->key;
        $sign_new = $this->sign($body, $key);
        if ($sign_new !== strtoupper($sign)) {
            return false;
        }
        return true;
    }
}
