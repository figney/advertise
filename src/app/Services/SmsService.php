<?php


namespace App\Services;


use App\Models\Sms;
use Carbon\Carbon;

class SmsService extends BaseService
{
    /**
     * 发送验证码
     * @param $national_number
     */
    public function sendSms($national_number)
    {
        $code = rand(1000, 9999);
        $last = Sms::query()->where('has_verify', false)->where(function ($q) use ($national_number) {
            $q->where('national_number', $national_number)->orWhere('ip', $this->getIP())->orWhere('imei', $this->getIMEI());
        })->orderByDesc('created_at')->first(['created_at']);

        abort_if($last?->created_at && Carbon::make($last->created_at)->gt(now()->addSeconds(-30)), 400, Lang('发送频率过快'));

        $sms = Sms::query()->create([
            'national_number' => (string)$national_number,
            'code' => $code,
            'imei' => $this->getIMEI(),
            'ip' => $this->getIP(),
            'local' => $this->getLocal(),
            'lang' => $this->getAgentLanguage(),
            'has_verify' => false,
        ]);
        return $sms;
    }

    public function checkCode($national_number, $code)
    {
        $code = (int)$code;
        $last = Sms::query()->where('created_at', '>', now()->addMinutes(-5))->orderByDesc('created_at')->where('national_number', (string)$national_number)->first();


        abort_if($last?->code !== $code, 400, Lang('验证码错误'));

        $last->has_verify = true;
        $last->save();

        return $last;
    }

    private function sendAwsSns($phone, $code)
    {
        try {
            $SnsClient = \AWS::createClient('sns');

            $result = $SnsClient->checkIfPhoneNumberIsOptedOut([
                'phoneNumber' => $phone,
            ]);
            abort_if($result['isOptedOut'], 400, Lang('发送失败'));
            $message = 'Your code is ' . $code;

            $SnsClient->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
            ]);
            return true;
        } catch (\Exception $exception) {
            \Log::error("短信发送失败：" . $exception->getMessage());
            return false;
        }


    }

}
