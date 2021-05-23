<?php


namespace App\Http\Controllers;


use App\Models\Share;
use App\Models\UserAdTask;
use App\Services\AppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShareController extends Controller
{

    public function share(Request $request)
    {


        $params = $request->getRequestUri();
        $params = str_replace("/?", "?", $params);
        $params = str_replace("&hash=", "#/", $params);

        $local = $request->input('lang');
        $uat = $request->input('uat');
        $local = AppService::make()->local($local);

        $web_url = Str::finish(Setting('web_url'), '/');

        $data = [];

        if ($uat) {
            $userAdTask = UserAdTask::query()->find($uat);
            $adTask = $userAdTask->adTask;
            $title = data_get($adTask->adData->share_content, $local);
            $describe = data_get($adTask->adData->describe, $local);
            $image = ImageUrl($adTask->adData->share_image);

            $title = str_replace("{URL}", "", $title);

        } else {
            $appShareInfo = Share::query()->inRandomOrder()->first();
            $title = data_get($appShareInfo->title, $local);
            $describe = data_get($appShareInfo->describe, $local);
            $image = data_get($appShareInfo->cover, $local);
        }


        if ($params == "/") $params = "";


        $go_url = $web_url . $params;

        $go_url = str_replace("/at", "", $go_url);


        $data['app_id'] = '';
        $data['url'] = $go_url;
        $data['site_name'] = '';
        $data['title'] = $title;
        $data['description'] = $describe;
        $data['image_url'] = $image;
        $data['go_url'] = $go_url;

        if (Str::contains($go_url, "s=ad")) {
            return redirect($go_url);
        }


        return view('share', $data);
    }
}
