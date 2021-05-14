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

class BananaPayService extends BaseService
{

    protected $merchant_no = "100xx";
    protected $merchant_key = "xxxxxx";  //商户秘钥 Merchant key
    private $api_domain = "https://api.banana-pay.com";  //API域名 API domain name(https://api.xxx.com)


    /**
     * @param RechargeChannel|WithdrawChannel $channel
     * @return $this
     */
    public function withConfig(WithdrawChannel|RechargeChannel $channel): static
    {

        $this->merchant_no = $channel->configValue('merchant_no');
        $this->merchant_key = $channel->configValue('merchant_key');


        return $this;

    }


    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code)
    {

        $params = array(
            'merchant_ref' => $userRechargeOrder->order_sn,//是	string	商户订单号 Merchant order number
            'product' => $rechargeChannelList->bank_code,//	是	string	产品名称 根据商户后台开通为主 product name Mainly based on the merchant backstage activation
            'amount' => $userRechargeOrder->amount,//	是	string	金额，单位，保留 2 位小数 Amount, unit, 2 decimal places
            //'extra'           => $extra,//	否	Object	额外参数， 默认为json字符串 {} Extra parameters, the default is json string {}
            //'language'        => '',//	否	string	收银台语言选择（详细请看语言代码） Cashier language selection (please see language code for details)
        );
        $extra = array(
            //	否	string	玩家付款账号【需同时传递 bank_code 字段】 Player payment account [need to pass the bank_code field]
            //'account_no' => '1234567890',

            //	否	string	玩家付款银行代码（详细请看银行代码）【需同时传递 account_no 字段】
            //Player's payment bank code (please see bank code for details) [need to pass the account_no field at the same time]
            //'bank_code' => 'KBANK',
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        if ($extra) {
            $params['extra'] = $extra;
        }
        $params_json = json_encode($params, 320);
        $data = array(
            'merchant_no' => $this->merchant_no,//	是	string	商户号 business number
            'timestamp' => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type' => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params' => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );
        $data['sign'] = $this->get_sign($data, $this->merchant_key);//MD5签名 不区分大小写

        $payUrl = $this->api_domain . '/api/gateway/pay';//API请求接口地址 API request interface address

        $res = \Http::asForm()->post($payUrl, $data);


        abort_if($res->clientError(), $res->status(), "The request failed");
        $res_data = $res->json();

        $status = data_get($res_data, "code") == 200;
        $message = data_get($res_data, "message");
        if (!$status) {
            $userRechargeOrder->delete();

            abort(400, $message);
        }

        $params = json_decode(data_get($res_data, "params"), true);


        return data_get($params, "payurl");
    }

    public function payInBack($request)
    {


        $data = isset($request['params']) ? json_decode($request['params'], true) : array();

        $order_sn = data_get($data, 'merchant_ref');
        $amount = (float)data_get($data, 'pay_amount', 0);
        $sign = data_get($request, 'sign');
        $platform_sn = data_get($data, 'system_ref');
        $payStatus = data_get($data, 'status') == 1;

        abort_if(!$order_sn, 400, "order_sn error");
        $order = UserRechargeOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$order, 400, "The order does not exist");

        if ($order->order_status == OrderStatusType::PaySuccess) return;

        abort_if($order->order_status !== OrderStatusType::Paying, 400, "Order status error");
        $rechargeChannel = $order->rechargeChannel;
        $this->withConfig($rechargeChannel);

        $data_sign = $this->get_sign($request, $this->merchant_key);
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
        //业务请求参数 Business request parameters
        $params = array(
            'merchant_ref' => $userWithdrawOrder->order_sn,//	是	string	商户订单号 Merchant order number
            'product' => "VNPayout",//	是	integer	产品名称 根据商户后台开通为主 product name Mainly based on the merchant backstage activation
            'amount' => (float)$userWithdrawOrder->actual_amount,//	是	string	金额，单位，保留 2 位小数 Amount, unit, 2 decimal places
            'account_name' => data_get($userWithdrawOrder->input_data, "account_name"),//	是	string	持卡人姓名 Cardholder's Name
            'account_no' => data_get($userWithdrawOrder->input_data, "account_no"),//	是	string	银行卡号 Bank card number
            'bank_code' => $userWithdrawOrder->withdrawChannelItem->bank_code,//	是	string	提现银行代码 Withdrawal bank code
        );

        //转换json串 Convert json string
        $params_json = json_encode($params, 320);

        //请求参数 Request parameter
        $data = array(
            'merchant_no' => $this->merchant_no,//	是	string	商户号 business number
            'timestamp' => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type' => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params' => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );

        $data['sign'] = $this->get_sign($data, $this->merchant_key);//MD5签名 不区分大小写 MD5 signature is not case sensitive

        $url = $this->api_domain . '/api/gateway/withdraw';

        $res = \Http::asForm()->post($url, $data);
        $res_data = $res->json();
        $status = data_get($res_data, "code") == 200;
        $message = data_get($res_data, "message");
        abort_if(!$status, 400, $message);

        $userWithdrawOrder->platform_sn = data_get($res_data, "system_ref");
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::Paying;
        $userWithdrawOrder->save();

    }

    public function payOutBack($request)
    {
        $data = isset($request['params']) ? json_decode($request['params'], true) : array();

        $order_sn = data_get($data, 'merchant_ref');

        $sign = data_get($request, 'sign');

        $payStatus = data_get($data, 'status') == 1;

        abort_if(!$order_sn, 400, "order_sn error");
        $userWithdrawOrder = UserWithdrawOrder::query()->where('order_sn', $order_sn)->first();
        abort_if(!$userWithdrawOrder, 400, "The order does not exist");
        if ($userWithdrawOrder->order_status == WithdrawOrderStatusType::CheckSuccess) return;
        abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Paying, 400, "Order status error");
        $channel = $userWithdrawOrder->withdrawChannel;
        $this->withConfig($channel);
        $data_sign = $this->get_sign($data, $this->merchant_key);
        if ($data_sign === $sign) {
            $userWithdrawOrder->back_time = now();
            if ($payStatus) {
                WithdrawService::make()->withdrawOrderSuccess($userWithdrawOrder);
            } else {
                $userWithdrawOrder->remark = data_get($request, "message", "pay error");
                WithdrawService::make()->withdrawOrderError($userWithdrawOrder);
            }

        } else {
            abort(400, "Bad Signature");
        }

    }


    public function get_sign($data = array(), $key = ''): string
    {
        //组装签名字段 签名 MD5(merchant_no+params+sign_type+timestamp+Key)-说明key 是商户秘钥
        //Assemble the signature field Signature MD5 (merchant_no+params+sign_type+timestamp+Key)-indicating that the key is the merchant secret key
        $merchant_no = $data['merchant_no'] ?? '';
        $params = $data['params'] ?? '';
        $sign_type = $data['sign_type'] ?? '';
        $timestamp = $data['timestamp'] ?? '';

        $sign_str = $merchant_no . $params . $sign_type . $timestamp . $key;
        //MD5签名 不区分大小写  MD5 signature is not case sensitive
        return md5($sign_str);
    }


    public function getBank()
    {
        return '[
    {
        "bank_code": "VCB",
        "bank_name": "Vietcombank"
    },
    {
        "bank_code": "CTG",
        "bank_name": "VietinBank"
    },
    {
        "bank_code": "TCB",
        "bank_name": "Techcombank"
    },
    {
        "bank_code": "BIDV",
        "bank_name": "BIDV"
    },
    {
        "bank_code": "VBAA",
        "bank_name": "Agribank"
    },
    {
        "bank_code": "SAEM",
        "bank_name": "Sacombank"
    },
    {
        "bank_code": "ACB",
        "bank_name": "Asia Bank"
    },
    {
        "bank_code": "MB",
        "bank_name": "MBBank"
    },
    {
        "bank_code": "TPB",
        "bank_name": "TPBank"
    },
    {
        "bank_code": "SHBK",
        "bank_name": "Shinhan Bank"
    },
    {
        "bank_code": "VIB",
        "bank_name": "VIB"
    },
    {
        "bank_code": "VPB",
        "bank_name": "VPBank"
    },
    {
        "bank_code": "SHB",
        "bank_name": "SHB"
    },
    {
        "bank_code": "OCB",
        "bank_name": "OCB"
    },
    {
        "bank_code": "EIB",
        "bank_name": "Eximbank"
    },
    {
        "bank_code": "BVB",
        "bank_name": "BaoViet Bank"
    },
    {
        "bank_code": "VIETCAPITALBANK",
        "bank_name": "Viet Capital Bank"
    },
    {
        "bank_code": "VRB",
        "bank_name": "VRB"
    },
    {
        "bank_code": "ABB",
        "bank_name": "ABBank"
    },
    {
        "bank_code": "PVCOMBANK",
        "bank_name": "PVcombank"
    },
    {
        "bank_code": "OJB",
        "bank_name": "OceanBank"
    },
    {
        "bank_code": "NAMA",
        "bank_name": "Nam A Bank"
    },
    {
        "bank_code": "HDB",
        "bank_name": "HDB"
    },
    {
        "bank_code": "VNTT",
        "bank_name": "VietBank"
    },
    {
        "bank_code": "VID",
        "bank_name": "Public Bank"
    },
    {
        "bank_code": "HLB",
        "bank_name": "Hong Leong Bank (HLB)"
    },
    {
        "bank_code": "PGB",
        "bank_name": "PG Bank"
    },
    {
        "bank_code": "CIMB",
        "bank_name": "CIMB"
    },
    {
        "bank_code": "NCB",
        "bank_name": "NCB"
    },
    {
        "bank_code": "IVB",
        "bank_name": "Indovina Bank"
    },
    {
        "bank_code": "EACB",
        "bank_name": "DongA Bank"
    },
    {
        "bank_code": "GPB",
        "bank_name": "GPBank"
    },
    {
        "bank_code": "BACA",
        "bank_name": "BAC A Bank"
    },
    {
        "bank_code": "VAB",
        "bank_name": "VietABank"
    },
    {
        "bank_code": "SBIT",
        "bank_name": "Saigonbank"
    },
    {
        "bank_code": "MSB",
        "bank_name": "Maritime Bank"
    },
    {
        "bank_code": "LVB",
        "bank_name": "LienVietPostBank"
    },
    {
        "bank_code": "KLB",
        "bank_name": "KienLongBank"
    },
    {
        "bank_code": "IBK",
        "bank_name": "Industrial Bank of Korea - IBK"
    },
    {
        "bank_code": "WOO",
        "bank_name": "Woori Bank"
    },
    {
        "bank_code": "SEA",
        "bank_name": "SeABank"
    },
    {
        "bank_code": "UOB",
        "bank_name": "UOB"
    }
]';
    }
}
