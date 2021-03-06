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
     * ????????????
     * @param array $data
     * @return string $result
     */
    public function sign($data)
    {
        //??????????????????????????????????????????
        ksort($data);
        $string = $this->toUrlParams($data);

        //?????????????????????string?????????KEY
        $string = $string . "&key=" . $this->key;
        //??????????????????MD5??????
        $string = md5($string);
        //??????????????????????????????????????????
        $result = strtoupper($string);
        return $result;
    }

    /**
     * ???????????????
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
     *  ??????
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
