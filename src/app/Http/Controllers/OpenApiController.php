<?php


namespace App\Http\Controllers;


use App\Enums\OrderStatusType;
use App\Models\Device;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OpenApiController extends Controller
{
    public function index(Request $request)
    {
        $type=(int)$request->get( 'type' );//type
        $token=$request->get( 'token' );//token
        if($type==0){//检测数据
            $minute=(int)$request->get( 'minute' );//分钟数
            if($minute==0){
                $minute=20;
            }
            $data=$this->check($minute);

        }else if($type==1){//总统计
            $data=$this->getall();
        }

        return response()->json(array(
            "code" => 200,
            "message" => "success",
            "data" => $data,
        ));

    }


    private function getall()
    {
        $platform_name=config('admin.name');
        $symbol=Setting('fiat_code');//货币单位

        $get_url = Setting('socket_url');
        $res = \Http::get($get_url)->collect();
        $online= (int)$res->get('allUserNum', 0);

        $taday=Carbon::today();
        $yesterday = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
        $yesterday_now=[Carbon::yesterday(), now()->addDays(-1)];

        //统计设备
        $os_today_count = Device::query()->where('created_at', '>=', $taday)->count();
        $os_yesterday_count = Device::query()->whereBetween('created_at', $yesterday)->count();
        $os_yesterday_count_now = Device::query()->whereBetween('created_at', $yesterday_now)->count();
        //统计用户
        $registered_today_count = User::query()->where('created_at', '>=', $taday)->count();
        $registered_yesterday_count = User::query()->whereBetween('created_at', $yesterday)->count();
        $registered_yesterday_count_now = User::query()->whereBetween('created_at', $yesterday_now)->count();

        //统计用户存款
        $deposit=UserRechargeOrder::query()->pay()->noTester();

        $deposit_today_count = (clone $deposit)->where('pay_time', '>=', $taday)->count();
        $deposit_yesterday_count = (clone $deposit)->whereBetween('pay_time', $yesterday)->count();
        $deposit_yesterday_count_now = (clone $deposit)->whereBetween('pay_time', $yesterday_now)->count();

        $deposit_today_money = (float)(clone $deposit)->where('pay_time', '>=', $taday)->sum('amount');
        $deposit_yesterday_money = (float)(clone $deposit)->whereBetween('pay_time', $yesterday)->sum('amount');
        $deposit_yesterday_money_now = (float)(clone $deposit)->whereBetween('pay_time', $yesterday_now)->sum('amount');

        $deposit_money_all = (float)(clone $deposit)->sum('amount');

        //统计用户提现
        $withdraw=UserWithdrawOrder::query()->pay()->noTester();

        $withdraw_today_count = (clone $withdraw)->where('pay_time', '>=', $taday)->count();
        $withdraw_yesterday_count = (clone $withdraw)->whereBetween('pay_time', $yesterday)->count();
        $withdraw_yesterday_count_now = (clone $withdraw)->whereBetween('pay_time', $yesterday_now)->count();

        $withdraw_today_money = (float)(clone $withdraw)->where('pay_time', '>=', $taday)->sum('amount');
        $withdraw_yesterday_money = (float)(clone $withdraw)->whereBetween('pay_time', $yesterday)->sum('amount');
        $withdraw_yesterday_money_now =(float)(clone $withdraw)->whereBetween('pay_time', $yesterday_now)->sum('amount');

        $withdraw_money_all = (float)(clone $withdraw)->sum('amount');

        //总收益
        $profit_money=$deposit_money_all-$withdraw_money_all;

        //手续费
        $deposit_money_fee=sprintf("%.2f",$deposit_money_all*0.07);
        $withdraw_money_fee=sprintf("%.2f",$withdraw_money_all*0.07);
        $sms_money_fee=0*0.2;//短信 0.2根据人民币换算
        $symbol=$symbol." ";
        $data=array(
            "platform_name"=>$platform_name,
            "symbol"=>$symbol,
            "online"=>$online,
            "os"=>array("today_count"=>$os_today_count,"yesterday_count"=>$os_yesterday_count,"yesterday_count_now"=>$os_yesterday_count_now),
            "registered"=>array("today_count"=>$registered_today_count,"yesterday_count"=>$registered_yesterday_count,"yesterday_count_now"=>$registered_yesterday_count_now),
            "deposit"=>array(
                "today_count"=>$deposit_today_count,"yesterday_count"=>$deposit_yesterday_count,"yesterday_count_now"=>$deposit_yesterday_count_now,
                "today_money"=>$deposit_today_money,"yesterday_money"=>$deposit_yesterday_money,"yesterday_money_now"=>$deposit_yesterday_money_now,
                "all_money"=>$deposit_money_all,
            ),
            "withdraw"=>array(
                "today_count"=>$withdraw_today_count,"yesterday_count"=>$withdraw_yesterday_count,"yesterday_count_now"=>$withdraw_yesterday_count_now,
                "today_money"=>$withdraw_today_money,"yesterday_money"=>$withdraw_yesterday_money,"yesterday_money_now"=>$withdraw_yesterday_money_now,
                "all_money"=>$withdraw_money_all,
            ),
            "profit"=>array(
                "today"=>sprintf("%.2f",$deposit_today_money-$withdraw_today_money),
                "yesterday"=>sprintf("%.2f",$deposit_yesterday_money-$withdraw_yesterday_money),
                "yesterday_now"=>sprintf("%.2f",$deposit_yesterday_money_now-$withdraw_yesterday_money_now),
                "all_money"=>$profit_money,
                "all_money_fee"=>sprintf("%.2f",$profit_money-$deposit_money_fee-$withdraw_money_fee-$sms_money_fee),
            ),
            "fee"=>array(
                "deposit_money_fee"=>$deposit_money_fee,
                "withdraw_money_fee"=>$withdraw_money_fee,
                "sms_money_fee"=>$sms_money_fee,
                "fee_title"=>"存款、提现",
            ),
        );

        return $data;
    }

    private function check($minute)
    {
        //用户注册======================================================
        $time = [Carbon::now()->addMinutes(-$minute), Carbon::now()];
        $user_count = User::query()->whereBetween('created_at', $time)->count();

        $minute_last=$minute*2;
        $time_last = [Carbon::now()->addMinutes(-$minute_last), Carbon::now()->addMinutes(-$minute)];
        $user_count_last= User::query()->whereBetween('created_at', $time_last)->count();

        $user_info=array(
            "user_count"=>$user_count,
            "user_count_last"=>$user_count_last,
        );

        //订单======================================================
        $recharge_count= UserRechargeOrder::query()->whereBetween('created_at', $time)->count();
        $recharge_success_count= UserRechargeOrder::query()->where('order_status', OrderStatusType::PaySuccess)->whereBetween('created_at', $time)->count();

        $recharge_info=array(
            "recharge_count"=>$recharge_count,
            "recharge_success_count"=>$recharge_success_count,
        );

        $data=array(
            "user_info"=>$user_info,
            "recharge_info"=>$recharge_info,
        );
        return $data;
    }

}
