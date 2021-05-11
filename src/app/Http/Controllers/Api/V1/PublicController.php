<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Models\LanguageConfig;
use App\Services\DeviceService;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicController extends ApiController
{


    /**
     * 设备注册-sbzc
     * @queryParam channel_id int required 渠道ID
     * @queryParam invite_id int required 邀请码
     * @queryParam link_id int required 链接ID
     * @queryParam source  required 来源
     * @queryParam source_url  required 来源地址
     */
    public function initDevice(Request $request)
    {

        try {
            $this->validatorData($request->all(), [
                'invite_id' => 'required|integer',
                'channel_id' => 'required|integer',
                'link_id' => 'required|integer',
                'source' => 'required',
                'source_url' => 'required',
            ]);
            DeviceService::make()->createDevice($request->all());
            return $this->responseMessage(Lang("SUCCESS"));
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * 设备行为轨迹-sbxwgj
     * @queryParam type  required 类型
     * @queryParam event_name  required 事件名称
     * @queryParam untitled_page  required 页面名称
     * @queryParam untitled_url  required 页面地址
     */
    public function deviceLog(Request $request)
    {

        try {
            $this->validatorData($request->all(), [
                'type' => 'required',
                'event_name' => 'required',
                'untitled_page' => 'required',
                'untitled_url' => 'required',
            ]);
            DeviceService::make()->createDeviceLog($request->all());
            return $this->responseMessage(Lang("SUCCESS"));
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * 切换语言-qhyy
     *
     * 用户切换语言后，请求此接口，注意：头信息需要是切换后最新的
     *
     */
    public function switchLanguage()
    {

        $user = $this->user();
        if ($user) {
            $user->local = $this->getLocal();
            $user->save();
        }
        $device = $this->device();
        if ($device) {
            $device->local = $this->getLocal();
            $device->save();
        }
        return $this->responseMessage(Lang("SUCCESS"));
    }


    /**
     * 获取分享内容-hqfxnr
     * @return \Illuminate\Http\JsonResponse
     */
    public function shareInfo()
    {
        $shareService = new ShareService();
        $contents = $shareService->getShare($this->getLocal());
        $url = null;
        $user = $this->user();
        $domain = $shareService->getDomain();
        if ($domain) {
            $url = Str::finish($domain->domain, '/');
            if ($user) $url .= "?t=" . $user->id;
        }
        return $this->response([
            'contents' => $contents,
            'url' => $url
        ]);

    }

    /**
     * 获取喇叭数据-hqlbsj
     * @return \Illuminate\Http\JsonResponse
     */
    public function annunciation()
    {

        $faker = $this->faker($this->getLocal());

        $list = collect();

        $actions = ['invite', 'deposit', 'profit', 'award'];


        for ($i = 0; $i <= 20; $i++) {

            $item = [
                'who' => $faker->name,
                'action' => collect($actions)->random(),
                'friend' => $faker->name,
                'fee' => $faker->numberBetween(1000000, 50000000),
            ];

            $list->add($item);

        }


        return $this->response($list);

    }

}
