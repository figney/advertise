<?php


namespace App\Services;


use App\Enums\HeaderType;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class DeviceService extends BaseService
{

    public function createDevice($data)
    {
        $agent = new Agent();

        $user = $this->user();

        $user_id = 0;


        if ($user) {
            $user_id = $user->id;
        }

        Device::query()->firstOrCreate(['imei' => $this->getIMEI()], [
            'ip' => $this->getIP(),
            'user_id' => $user_id,
            'is_app' => $this->getHeader(HeaderType::IsApp) == "true",
            'lang' => Str::upper(collect($agent->languages())->first()),
            'local' => $this->getLocal(),
            'version' => $this->getHeader(HeaderType::Version),
            'browser_name' => $this->getHeader(HeaderType::BrowserName),
            'browser_version' => $this->getHeader(HeaderType::BrowserVersion),
            'brand' => $this->getHeader(HeaderType::Brand),
            'model' => $this->getHeader(HeaderType::Model),
            'width' => (int)$this->getHeader(HeaderType::Width),
            'height' => (int)$this->getHeader(HeaderType::Height),
            'os' => $this->getHeader(HeaderType::Os),
            'timezone' => $this->getHeader(HeaderType::Timezone),
            'channel_id' => data_get($data, 'channel_id', 1),
            'link_id' => data_get($data, 'link_id', 0),
            'invite_id' => data_get($data, 'invite_id'),
            'source' => data_get($data, 'source'),
            'source_url' => data_get($data, 'source_url'),

        ]);
    }


    public function createDeviceLog($data)
    {
        $agent = new Agent();

        $user = $this->user();

        $user_id = 0;

        if ($user) {
            $user_id = $user->id;
        }

        DeviceLog::query()->create([
            'imei' => $this->getIMEI(),
            'user_id' => data_get($user, 'id', 0),
            'channel_id' => data_get($user, 'channel_id', 0),
            'link_id' => data_get($user, 'link_id', 0),
            'ip' => $this->getIP(),
            'is_app' => $this->getHeader(HeaderType::IsApp) == "true",
            'lang' => Str::upper(collect($agent->languages())->first()),
            'local' => $this->getLocal(),
            'version' => $this->getHeader(HeaderType::Version),
            'os' => $this->getHeader(HeaderType::Os),
            'type' => data_get($data, 'type'),
            'event_name' => data_get($data, 'event_name'),
            'untitled_page' => data_get($data, 'untitled_page'),
            'untitled_url' => data_get($data, 'untitled_url'),
        ]);
    }
}
