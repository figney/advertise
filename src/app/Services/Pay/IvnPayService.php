<?php


namespace App\Services\Pay;


use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;
use App\Services\BaseService;

class IvnPayService extends BaseService implements InPay
{

    protected string $host = "https://test-apipay.ivnpay.cc";
    protected $secret_key = "";
    protected $mch_id = "";


    public function withConfig(WithdrawChannel|RechargeChannel $channel)
    {
        $this->secret_key = $channel->configValue('secret_key');

        $this->mch_id = $channel->configValue('mch_id');
        return $this;
    }

    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {
        $params = array(
            "mch_id" => $this->mch_id,
            "mch_uid" => $user->id,
            "mch_order_id" => $userRechargeOrder->order_sn,
            "equipment_type" => 0,
            "expected_mount" => (float)$userRechargeOrder->amount,
            "mch_url" => "",
            "show_types" => "4+40"
        );
        $param = $this->gen_param($params);
        return $this->host . "/public/comm/index.html?param=" . $param;
    }

    public function payInBack($request)
    {
        // TODO: Implement payInBack() method.
    }

    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel)
    {
        // TODO: Implement payOut() method.
    }

    public function payOutBack($data)
    {
        // TODO: Implement payOutBack() method.
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
