<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\SmsService;
use App\Services\UserHookService;
use App\Services\UserService;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class AuthController extends ApiController
{

    public function __construct(protected UserService $userService, protected SmsService $smsService, protected UserHookService $userHookService)
    {
    }


    /**
     * 获取用户昵称-hqyhnc
     * @group 用户-User
     * @return \Illuminate\Http\JsonResponse
     */
    public function getName()
    {
        $faker = $this->faker();
        $name = $faker->name;
        return $this->response(['name' => $name]);
    }

    /**
     * 发送注册验证码-zcyzm
     * @queryParam national_number int required 手机号码
     * @queryParam country_code  required 国家码
     * @queryParam g_token  required 谷歌验证token
     * @group 用户-User
     */
    public function sendRegisterSms(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'national_number' => 'required|phone:country_code',
                'g_token' => 'required',
                'country_code' => 'required|required_with:phonefield',
            ], [
                'national_number.phone' => Lang("手机号码错误")
            ]);
            $data = $request->all();
            $g_token = $data['g_token'];
            $country_code = Str::upper($request->input('country_code'));
            //处理手机号码
            $data['national_number'] = (string)PhoneNumber::make($request->input('national_number'), $country_code);
            $data['country_code'] = $country_code;


            //谷歌验证码检测
            $this->userService->checkGoogleRecaptcha($g_token, $data['national_number']);
            //手机号码重复检测
            $this->userService->checkNationalNumber($data['national_number']);
            //发送验证码
            $sms = $this->smsService->sendSms($data['national_number']);
            $sms->update(['country_code' => $country_code]);

            return $this->responseMessage(Lang("发送成功"));

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }

    /**
     * 用户登录-yhdl
     * @queryParam national_number int required 手机号码
     * @queryParam country_code  required 国家码
     * @queryParam password  required 密码
     * @queryParam g_token  required 谷歌验证token
     * @group 用户-User
     * @param Request $request
     */
    public function login(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'national_number' => 'required|phone:country_code',
                'g_token' => 'required',
                'country_code' => 'required|required_with:phonefield',
                'password' => 'required',
            ]);
            $data = $request->only('national_number', 'password');


            $g_token = $request->input('g_token');

            $userService = new UserService();


            $country_code = Str::upper($request->input('country_code'));

            abort_if(!in_array($country_code, Setting('country_code')), 400, 'country code error');

            //处理手机号码
            $data['national_number'] = PhoneNumber::make($request->input('national_number'), $country_code);


            //谷歌验证码检测
            $checkRes = $userService->checkGoogleRecaptcha($g_token, $data['national_number']);

            if ($token = auth('api')->attempt($data)) {
                /** @var User $user */
                $user = auth('api')->user();

                abort_if(!$user->status, 400, Lang("账户异常"));

                //判断登录设备
                if (!empty($user->imei) && $user->imei != $this->getIMEI()) {
                    //abort(400, Lang('请勿多设备登录相同账号'));
                }


                //更新用户imei
                if (empty($user->imei) /*|| $user->imei != $this->getIMEI()*/) {
                    $user->imei = $this->getIMEI();
                    $user->save();
                }

                //记录用户登录日志
                UserLoginLog::query()->create([
                    'action' => 'login',
                    'user_id' => $user->id,
                    'user_imei' => $user->imei,
                    'login_imei' => $this->getIMEI(),
                    'ip' => $this->getIP(),
                    'recaptcha' => $checkRes,
                ]);
                //更新设备绑定用户
                $user->device()->where('user_id', 0)->update(['user_id' => $user->id]);


                //触发钩子
                $this->userHookService->loginHook($user);

                return $this->response(
                    [
                        'user' => UserResource::make($user),
                        'token' => $token,
                    ]);
            }
            return $this->responseError(Lang("用户名或密码错误"));

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }


    }


    /**
     * 用户注册-yhzc
     * @queryParam name required 用户昵称
     * @queryParam national_number int required 手机号码
     * @queryParam country_calling_code int required 手机区号
     * @queryParam country_code  required 国家码
     * @queryParam sms_code int  required 短信验证码
     * @queryParam invite_id int  required 邀请码
     * @queryParam channel_id int  required 渠道ID  没有传 1
     * @queryParam link_id int  required 推广链接ID 没有传0
     * @queryParam password  required 密码
     * @queryParam password_confirmation  required 确认密码
     * @queryParam source  required 来源
     * @queryParam g_token  required 谷歌验证token
     * @group 用户-User
     * @param Request $request
     */
    public function register(Request $request)
    {
        try {

            $this->validatorData($request->all(), [
                'name' => 'required|max:32',
                'national_number' => 'required|phone:country_code',
                'country_calling_code' => 'required',
                'country_code' => 'required|required_with:phonefield',
                'sms_code' => 'required|integer',
                'invite_id' => 'required|integer',
                'channel_id' => 'required|integer',
                'link_id' => 'required|integer',
                'source' => 'required',
                'g_token' => 'required',
                'password' => 'required|confirmed',
            ], [
                'national_number.phone' => Lang("手机号码错误")
            ]);

            $userService = new UserService();

            $data = $request->all();

            $g_token = $data['g_token'];
            $sms_code = data_get($data, 'sms_code');


            $country_code = Str::upper($request->input('country_code'));

            abort_if(!in_array($country_code, Setting('country_code')), 400, 'country code error');

            //处理手机号码
            $data['national_number'] = (string)PhoneNumber::make($request->input('national_number'), $country_code);

            $data['country_code'] = $country_code;

            //谷歌验证码检测
            $checkRes = $userService->checkGoogleRecaptcha($g_token, $data['national_number']);

            //短信验证码检测
            if (Setting('is_sms_reg')) {
                $sms = $this->smsService->checkCode($data['national_number'], $sms_code);
            }


            //创建用户逻辑
            $user = $userService->createUser($data);

            if (Setting('is_sms_reg')) {
                //标记短信成功
                $sms->user_id = $user->id;
                $sms->save();
            }


            $token = auth('api')->login($user);


            //记录用户登录日志
            UserLoginLog::query()->create([
                'action' => 'register',
                'user_id' => $user->id,
                'user_imei' => $user->imei,
                'login_imei' => $this->getIMEI(),
                'ip' => $this->getIP(),
                'recaptcha' => $checkRes,
            ]);

            //触发钩子
            $this->userHookService->registerHook($user);

            return $this->response(
                [
                    'user' => UserResource::make($user),
                    'token' => $token,
                ]);


        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

}
