<?php


namespace App\Traits;


use App\Enums\HeaderType;
use App\Models\Device;
use App\Models\User;
use App\Services\AppService;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

trait AppBase
{


    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null|User
     */
    public function user()
    {


        /* @var User $user */
        $user = auth('api')->user();
        if ($user) {
            abort_if(!$user->status, 401, Lang('用户异常'));
            if (!$user->last_active_at || Carbon::make($user->last_active_at)->lt(now()->subMinutes(5))) {
                $user->last_active_at = now();
                $user->lang = $this->getAgentLanguage();
                $user->local = $this->getLocal();
                $user->save();
            }
        }
        return $user;
    }

    public function device()
    {
        return Device::query()->where('imei', $this->getIMEI())->first();
    }

    //获取客户端ip
    public function getIP(): string
    {
        try {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                //ip from share internet
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                //ip pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        } catch (\Exception $exception) {
            return '';
        }
    }

    public function getIMEI(): string
    {
        return request()->header(HeaderType::IMEI, "empty");
    }

    public function getHeader($key)
    {
        return request()->header($key, "");
    }

    public function getLocal(): string
    {
        //获取当前语言
        return AppService::make()->local();
    }

    public function getAgentLanguage(): string
    {
        $agent = new Agent();
        $lang = Str::upper(collect($agent->languages())->first());

        $lang = str_replace("-", "_", $lang);

        return $lang;

    }


    public function validatorData(array $all, $rules, $message = [], \Closure $closure = null): \Illuminate\Validation\Validator
    {
        $validator = \Validator::make($all, $rules, $message);

        if ($closure) {
            call_user_func($closure, $validator);
        }

        if ($validator->fails()) {
            abort(400, $validator->errors()->first());
        }
        return $validator;
    }


    protected function response($data, $message = '', $code = 200): JsonResponse
    {

        $re_data = [
            'code' => $code,
            'local' => AppService::make()->local(),
            'message' => $message,
            'data' => is_array($data) ? [] : null,
        ];
        if ($data) {
            $re_data['data'] = $data;
        }
        return \Response::json($re_data, $code);
    }

    protected function responseMessage($message, $code = 200): JsonResponse
    {
        return $this->response(null, $message, $code);
    }

    protected function responseError($message, $code = 400): JsonResponse
    {
        return $this->response(null, $message, $code);
    }

    protected function responseException(\Exception $exception, $code = 400): JsonResponse
    {
        $message = $exception->getMessage();
        return $this->response(null, $message, $code);
    }

    protected function faker(): Generator
    {
        $local = config('env.local');
        return Factory::create($local);
    }
}
