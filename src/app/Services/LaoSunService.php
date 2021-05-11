<?php


namespace App\Services;


use App\Enums\WithdrawOrderStatusType;
use App\Models\CoinAddress;
use App\Models\RechargeChannel;
use App\Models\UserWithdrawOrder;
use Ramsey\Uuid\Uuid;

class LaoSunService extends BaseService
{
    private string $url = "http://60.205.223.116:8680";
    private string $api_key;
    private string $api_key_v2;
    private int $merchant_id;

    public function setConfig($api_key, $api_key_v2, $merchant_id)
    {
        $this->api_key = $api_key;
        $this->api_key_v2 = $api_key_v2;
        $this->merchant_id = $merchant_id;

        return $this;

    }

    public function setChannel(RechargeChannel $rechargeChannel)
    {
        $this->api_key = $rechargeChannel->configValue('api_key');
        $this->api_key_v2 = $rechargeChannel->configValue('api_key_v2');
        $this->merchant_id = $rechargeChannel->configValue('merchant_id');

        return $this;
    }


    public function cash(UserWithdrawOrder $userWithdrawOrder)
    {
        $url = $this->url . '/api/cash';

        $address = data_get($userWithdrawOrder->input_data, "address");
        abort_if(!$address, 400, "地址错误");
        $param["timestamp"] = time(); //时间戳
        $param['merchant_id'] = $this->merchant_id;
        $param["user_id"] = $userWithdrawOrder->user_id;
        $param["trade_id"] = $userWithdrawOrder->order_sn; //
        $param["currency"] = "USDT.TRC20"; //币名称
        $param["amount"] = $userWithdrawOrder->actual_amount; //金额
        $param["sign_type"] = "rsa"; //加密类型
        $param["address"] = $address; //地址
        $param['sign'] = $this->getSign($param);
        $request = $this->request_post($url, $this->arraySign($param));
        $request = json_decode($request, true);

        abort_if(!is_array($request), 400, "请求失败");

        \Log::info("LaoSun提币申请:" . $userWithdrawOrder->order_sn, $request);

        if (isset($request['result']) && $request['result'] == 'SUCCESS') {
            $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
            $userWithdrawOrder->save();
        } else {
            abort(400, $request['message']);
        }

    }

    public function createAddress($rechargeChannel_id)
    {
        $id = Uuid::uuid1()->toString();

        $data = $this->getAddress($id);
        $address = data_get($data, 'data.address');
        $currency = data_get($data, 'data.currency');

        if ($address) {
            CoinAddress::query()->firstOrCreate(['address' => $address], [
                'currency' => $currency,
                'uuid' => $id,
                'recharge_channel_id' => $rechargeChannel_id
            ]);
        }
    }

    /**
     * @param string $user_id
     * @return array|mixed
     */
    public function getAddress(string $user_id)
    {

        $data["currency"] = "USDT.TRC20";//币名称
        $data["merchant_id"] = $this->merchant_id;//商户号
        $data['sign_type'] = "md5";
        $data["timestamp"] = time();//时间戳
        $data['user_id'] = $user_id;

        $sign = "currency=" . $data["currency"] . "&merchant_id=" . $data["merchant_id"] . "&sign_type=" . $data["sign_type"] . "&timestamp=" . $data["timestamp"] . "&user_id=" . $data["user_id"] . $this->api_key;
        $data["sign"] = md5($sign);

        $url = $this->url . "/api/address";

        $request = $this->request_post($url, $this->arraySignByAddress($data));
        $request = json_decode($request, true);

        return $request;

    }

    /**
     * 验证签名
     */
    public function checkSign($data)
    {
        $signString = $this->getSignString($data);
        if (isset($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            $priKey = $this->formatPriKey($this->api_key_v2);
            openssl_private_decrypt(base64_decode($sign), $decrypted, $priKey); //私钥解密
            $a = bin2hex($decrypted);
            $b = hash("sha256", $signString);
            if ($a == $b) {
                return true;
            }
            return false;
        }
    }

    /**
     * 格式化私钥
     */
    private function formatPriKey($priKey)
    {
        $fKey = "-----BEGIN PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PRIVATE KEY-----";
        return $fKey;
    }

    /**
     * 获取待签名字符串
     * @param array $params 参数数组
     * @return   string
     */
    private function getSignString($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        if ($params) {
            ksort($params);
            reset($params);
            $pairs = array();
            foreach ($params as $k => $v) {
                if (!empty($v)) {
                    $pairs[] = "$k=$v";
                }
            }
            return implode('&', $pairs);
        } else {
            return "";
        }
    }


    protected function getSign($param): string
    {

        $data = json_encode($param);
        $pikey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->api_key_v2, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($pikey);
        openssl_sign($data, $signature, $key, "SHA256");

        return urlencode(base64_encode($signature));

    }

    private function arraySignByAddress($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        $data = '';
        $i = 0;
        foreach ($arr as $arr_key => $arr_val) {
            if ($i == 0) {
                $data .= $arr_key . '=' . $arr_val;
            } elseif ($arr_key == 'timestamp') {
                $data .= '&timestamp' . '=' . $arr_val;
            } else {
                $data .= '&' . $arr_key . '=' . $arr_val;
            }
            $i++;
        }
        return $data;
    }


    private function arraySign($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        $data = '';
        $i = 0;
        foreach ($arr as $arr_key => $arr_val) {
            if ($i == 0) {
                $data .= $arr_key . '=' . $arr_val;
            } elseif ($arr_key == 'timestamp') {
                $data .= '&amp;timestamp' . '=' . $arr_val;
            } else {
                $data .= '&' . $arr_key . '=' . $arr_val;
            }
            $i++;
        }
        return $data;
    }

    function request_post($url = '', $param = '')
    {

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8', 'Content-Length: ' . strlen($param))); //application/json
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


}
