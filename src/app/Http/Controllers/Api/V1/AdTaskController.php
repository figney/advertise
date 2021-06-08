<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\UserAdTaskType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AdTaskResource;
use App\Http\Resources\UserAdTaskResource;
use App\Models\UserAdTask;
use App\Services\AdTaskService;
use Illuminate\Http\Request;
use App\Services\AppService;

class AdTaskController extends ApiController
{

    public function __construct(protected AdTaskService $adTaskService)
    {
    }


    public function share1(Request $request)
    {
        $this->validatorData($request->all(), [
            'uat' => 'required|integer',
            'lang' => 'required',
        ]);

        $local = $request->input('lang');
        $uat = $request->input('uat');
        $local = AppService::make()->local($local);

        $data = [];

        $userAdTask = UserAdTask::query()->find($uat);
        $adTask = $userAdTask->adTask;
        $title = data_get($adTask->adData->share_content, $local);
        $content = data_get($adTask->adData->content,$local);
        $describe = data_get($adTask->adData->describe, $local);
        $image = ImageUrl($adTask->adData->share_image);

        $title = str_replace("{URL}", "", $title);

        $data['app_id'] = '';
        $data['site_name'] = '';
        $data['content'] = $content;
        $data['title'] = $title;
        $data['description'] = $describe;
        $data['image_url'] = $image;


        return $data;
    }
    /**
     * 获取广告任务列表-adTaskList
     * @queryParam level int 等级 0为免费
     * @queryParam tag string 标签  hot  rec
     * @queryParam page int   页码
     * @queryParam page_size int
     * @queryParam random bool
     * @group 广告任务-ggrw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adTaskList(Request $request)
    {
        try {
            $page_size = (int)$request->input('page_size', 20);

            $orm = $this->adTaskService->getOrm()->with('adData:id,ad_task_id,title');
            $level = (int)$request->input('level', -1);
            $tag = $request->input('tag');
            $random = $request->boolean('random');
            if ($level >= 0) {
                $orm->where('vip_level', $level);
            }

            if ($tag) {
                $orm->whereJsonContains('tags', $tag);
            }
            if ($random) {
                $orm->inRandomOrder();
            } else {
                $orm->orderByDesc('order');
            }

            $list = $orm->paginate($page_size);
            return $this->response(['list' => AdTaskResource::collection($list)]);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }


    /**
     * 获取广告任务详情-adTaskDetails
     * @queryParam id required 任务ID
     * @queryParam free bool  是否免费任务，免费任务随机返回一条
     * @group 广告任务-ggrw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adTaskDetails(Request $request)
    {
        try {
            $user = $this->user();

            $this->validatorData($request->all(), [
                "id" => 'required',
            ]);
            $orm = $this->adTaskService->getOrm()->with(['adData']);


            if ($user) {
                $orm->with(['userAdTask' => function ($query) use ($user) {
                    $query->where('user_id', $user->id,)
                        ->inProgress();
                }]);
            }

            if ($request->boolean('free')) {
                $adTask = $orm->where('vip_level', 0)->inRandomOrder()->first();
            } else {
                $adTask = $orm->find($request->input('id'));
            }


            abort_if(!$adTask, 400, Lang('ARGS_ERROR'));


            return $this->response(['ad_task' => AdTaskResource::make($adTask)]);


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }


    /**
     * 接取广告任务-takeTheAdTask
     * @queryParam id required 任务ID
     * @group 广告任务-ggrw
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function takeTheAdTask(Request $request)
    {

        $user = $this->user();

        $id = $request->input('id');

        $lock = \Cache::lock("takeTheAdTask:" . $id, 10);

        try {
            $lock->block(10);
            $this->validatorData($request->all(), [
                "id" => 'required',
            ]);

            $adTask = $this->adTaskService->getOrm()->find($id);

            abort_if(!$adTask, 400, Lang('ARGS_ERROR'));

            $userAdTask = $this->adTaskService->userReceiveAdTask($user, $adTask);

            return $this->response(['user_ad_task' => UserAdTaskResource::make($userAdTask)]);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }

    }


    /**
     * 用户广告列表-userAdTaskList
     * @queryParam status string 任务状态
     * @group 广告任务-ggrw
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userAdTaskList(Request $request)
    {
        try {

            $status = $request->input('status');

            $user = $this->user();

            $orm = $user->adTasks()->with(['adTask', 'adTask.adData:id,ad_task_id,title']);

            if ($status && in_array($status, UserAdTaskType::getValues())) {
                $orm->where('status', $status);
            }

            $list = $orm->get();


            return $this->response(['list' => UserAdTaskResource::collection($list)]);


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }


    /**
     * 用户广告任务今日消耗-userAdTaskDetails
     * @group 广告任务-ggrw
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userAdTaskDetails(Request $request)
    {
        try {

            $user = $this->user();

            $count = $user->adTasks()
                ->groupBy('level')
                ->select(['level', \DB::raw('count(*) as count')])
                ->today()
                ->where(function ($q) {
                    $q->expiredCount()
                        ->orWhere(fn($qq) => $qq->inProgress())
                        ->orWhere(fn($qq) => $qq->finished());
                })->get()->pluck('count', 'level');

            return $this->response($count);


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }


    /**
     * 广告任务落地页打开-adTaskCheck
     * @queryParam uat required aut参数
     * @group 广告任务-ggrw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adTaskCheck(Request $request)
    {
        $userAdTaskId = $request->input("uat");

        \Log::info($userAdTaskId);

        $lock = \Cache::lock('adTaskCheck:' . $userAdTaskId, 10);

        try {
            $lock->block(10);

            $this->validatorData($request->all(), [
                "uat" => 'required',
            ]);


            $userAdTask = UserAdTask::query()->with(['adTask', 'adTask.adData', 'user'])->find($userAdTaskId);

            abort_if(!$userAdTask, 400, Lang("EMPTY"));

            $this->adTaskService->userAdTaskLog($userAdTask);

            \Log::info('2222');
            return $this->response(['user_ad_task' => UserAdTaskResource::make($userAdTask)]);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }
    }

}
