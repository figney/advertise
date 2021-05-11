<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserVipBuyListResource;
use App\Http\Resources\UserVipResource;
use App\Http\Resources\VipResource;
use App\Services\AdTaskService;
use App\Services\UserService;
use App\Services\VipService;
use Illuminate\Http\Request;

class VipController extends ApiController
{


    public function __construct(protected VipService $vipService)
    {
    }

    /**
     * 获取会员套餐列表-hyhytclb
     * @group 会员-vip
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVipList()
    {
        try {
            $orm = $this->vipService->getListOrm()->orderByDesc('order');


            $list = $orm->get();

            $data['list'] = VipResource::collection($list);
            return $this->response($data);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

    /**
     * 用户开通VIP-yhktvip
     * @queryParam id  required 套餐ID
     * @queryParam day  required 天数
     * @queryParam number  required 叠加次数
     * @group 会员-vip
     * @authenticated
     * @param Request $request
     */
    public function userBuyVip(Request $request)
    {
        $user = $this->user();
        $lock = \Cache::lock('userBuyVip:' . $user->id, 10);
        try {
            $lock->block(10);

            $this->validatorData($request->all(), [
                'id' => 'required',
                'day' => 'required',
                'number' => 'required',
            ]);
            $id = $request->input('id');
            $number = $request->input('number');
            $day = $request->input('day');

            $vip = $this->vipService->getListOrm()->findOrFail($id);

            $this->vipService->buyUserVip($user, $vip, $day, $number);

            $user = UserService::make()->getUserInfo();


            return $this->response(['user' => UserResource::make($user)]);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }

    }

    /**
     * 用户开通VIP记录明细-userBuyVipList
     * @queryParam level  VIP等级，默认返还全部等级
     * @group 会员-vip
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userBuyVipList(Request $request)
    {
        try {

            $level = $request->input('level', 0);
            $user = $this->user();

            $orm = $user->vips()->with('vip');
            if ($level > 0) {
                $orm->where('level', $level);
            }
            $list = $orm->get();


            $res['list'] = UserVipBuyListResource::collection($list);


            return $this->response($res);


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

    /**
     * 用户VIP详情-userVipInfo
     * @group 会员-vip
     * @authenticated
     * @param Request $request
     */
    public function userVipInfo()
    {

        $user = $this->user();

        try {
            $userVip = VipService::make()->getUserVipList($user);


            $ld = AdTaskService::make()->getUserTodayAdTaskCount($user);


            $res['vip'] = UserVipResource::collection(collect($userVip)->sortBy('level')->all());
            $res['ad_task_data'] = count($ld) > 0 ? $ld : null;

            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }

}
