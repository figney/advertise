<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ChannelServiceResource;
use App\Http\Resources\LanguageResource;
use App\Http\Resources\UserResource;
use App\Services\ChannelService;
use App\Services\LangService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class InitController extends ApiController
{

    /**
     * 初始化-init
     *
     *
     */
    public function init()
    {

        $service = ChannelService::make()->getUserService();

        //免费广告任务次数
        $res['free_task_num'] = Setting('free_task_num', 1);


        //用户客服
        $res['service'] = $service ? ChannelServiceResource::make($service) : null;

        //默认货币符号
        $res['default_currency'] = Setting('default_currency');
        $res['fiat_code'] = Setting('fiat_code');
        $res['usdt_money_rate'] = Setting('usdt_money_rate');
        $res['show_suffix'] = SettingBool('show_suffix');

        //小数位
        $res['coin_unit'] = [
            'balance' => Setting('money_decimal'),
            'usdt' => Setting('usdt_decimal'),
        ];

        $res['time_format'] = Setting('time_format');
        //国家码
        $res['country_code'] = Setting('country_code');
        $res['is_sms_reg'] = Setting('is_sms_reg');

        //语言列表
        $res['lang_list'] = LanguageResource::collection(LangService::make()->getLangList());
        $res['default_lang'] = Setting('default_lang');

        $res['i18n'] = LangService::make()->getIn18n('AdWeb');

        //时区
        $res['timezone'] = config('app.timezone');

        $res['left_secs'] = Carbon::now()->endOfDay()->floatDiffInSeconds(now());


        //谷歌验证KEY
        $res['web_recaptcha_key'] = Setting('google_web_recaptcha');
        $res['open_recaptcha'] = SettingBool('open_recaptcha');

        //APP相关
        $res['app'] = [
            'app_version_code' => Setting('app_version_code'),
            'app_download_url' => Setting('app_download_url'),
        ];


        //充值
        $res['recharge'] = [
            'open_recharge' => SettingBool('open_recharge'),
            'close_recharge_describe' => SettingBool('open_recharge') ? null : SettingLocal('close_recharge_describe'),
        ];
        //流水分组
        $res['wallet_slug'] = collect(WalletLogSlug::asArray())->map(function ($item) {
            return Lang(Str::upper($item));
        })->toArray();

        \App::isLocal() && $res['action_type'] = WalletLogType::asSelectArray();


        //赚钱宝年化率
        $res['money_bao_rate'] = [
            'mb_balance_rate' => Setting('mb_balance_rate'),
            'mb_usdt_rate' => Setting('mb_usdt_rate'),
            'mb_give_rate' => Setting('mb_give_rate'),
        ];

        $wallet_type = [WalletType::give, WalletType::balance];

        $has_login = auth('api')->check();

        $res['has_login'] = $has_login;
        $res['user'] = null;
        if ($has_login) {
            $user = UserService::make()->getUserInfo();
            $res['user'] = UserResource::make($user);

            if ($user->hasRecharge()) {
                $wallet_type = [WalletType::balance, WalletType::give,];
            }
        }


        $res['wallet_type'] = collect($wallet_type)->map(function ($item, $key) {
            return ["key" => $item, "value" => Lang(Str::upper($item))];
        })->toArray();


        return $this->response($res);

    }

    public function webJs()
    {
        $web_js_code = Setting('web_js_code');

        return response($web_js_code, 200, [
            'Content-Type' => 'text/javascript'
        ]);
    }


    /**
     * 前端国际化配置-in18n
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function in18n()
    {
        return $this->response(LangService::make()->getIn18n());
    }

}
