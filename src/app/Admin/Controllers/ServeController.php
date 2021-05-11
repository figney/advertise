<?php


namespace App\Admin\Controllers;


use App\Enums\QueueType;
use App\Jobs\SocketIoToAll;
use App\Models\UserSignInLog;
use App\Services\AppService;
use Carbon\Carbon;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class ServeController extends AdminController
{

    /**
     * 获取在线人数
     * @return mixed
     */
    public function getOnlineNum()
    {
        return AppService::make()->getOnlineNumber();
    }

    /**
     * 获取签到数据
     */
    public function getSignData()
    {

        //今日签到人数
        $today_count = UserSignInLog::query()->byChannel()->where('created_at', '>=', Carbon::today())->count();
        //昨日签到人数
        $yesterday_count = UserSignInLog::query()->byChannel()->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()])->count();
        //昨日此时签到人数
        $yesterday_time_count = UserSignInLog::query()->byChannel()->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::now()->addDays(-1)])->count();

        return [
            'today_count' => $today_count,
            'yesterday_count' => $yesterday_count,
            'yesterday_time_count' => $yesterday_time_count,
        ];

    }

    public function wsReload(Request $request)
    {
        $version = $request->input('version');
        if (!$version) return "请输入version参数";
        dispatch(new SocketIoToAll('version', ['version' => $version]))->onQueue(QueueType::allSend);

    }

}
