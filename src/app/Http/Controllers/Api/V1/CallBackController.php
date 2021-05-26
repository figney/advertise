<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Services\OnlinePayService;
use App\Services\Pay\BananaPayService;
use App\Services\Pay\FPayTHBService;
use App\Services\Pay\IPayIndianService;
use App\Services\Pay\IvnPayService;
use App\Services\Pay\JstPayService;
use App\Services\Pay\PayPlusService;
use App\Services\Pay\YudrsuService;
use Illuminate\Http\Request;

class CallBackController extends ApiController
{


    public function __construct(protected OnlinePayService $onlinePayService)
    {
    }


    /**
     * payPlus支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function payPlusInBack(Request $request)
    {
        try {
            \Log::info('payPlus支付回调：', $request->all());
            PayPlusService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::warning('payPlus支付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * payPlus代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function payPlusOutBack(Request $request)
    {
        try {
            \Log::info('payPlus代付回调：', $request->all());
            PayPlusService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::warning('payPlus代付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * ivnPay支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function ivnPayInBack(Request $request)
    {
        try {
            \Log::info('ivnPay支付回调：', $request->all());
            IvnPayService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('ivnPay支付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * ivnPay代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function ivnPayOutBack(Request $request)
    {
        try {
            \Log::info('ivnPay代付回调：', $request->all());
            IvnPayService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('ivnPay代付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }


    /**
     * bananaPay支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function bananaPayInBack(Request $request)
    {
        try {
            \Log::info('bananaPay支付回调：', $request->all());
            BananaPayService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('bananaPay支付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * bananaPay代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function bananaPayOutBack(Request $request)
    {
        try {
            \Log::info('bananaPay代付回调：', $request->all());
            BananaPayService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('bananaPay代付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }


    /**
     * jstPay支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function jstPayInBack(Request $request)
    {
        try {
            \Log::info('jstPay支付回调：', $request->all());
            JstPayService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::warning('jstPay支付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * jstPay代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function jstPayOutBack(Request $request)
    {
        try {
            \Log::info('jstPay代付回调：', $request->all());
            JstPayService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::warning('jstPay代付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * yudrsu支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return string
     */
    public function yudrsuPayInBack(Request $request)
    {
        try {
            \Log::info('yudrs支付回调：', $request->all());
            YudrsuService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('yudrs支付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }

    }

    /**
     * yudrsu代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return string
     */
    public function yudrsuPayOutBack(Request $request)
    {
        try {
            \Log::info('yudrs代付回调：', $request->all());
            YudrsuService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "SUCCESS";
        } catch (\Exception $exception) {
            \Log::warning('yudrs代付回调失败：' . $exception->getMessage());
            return $this->responseMessage($exception->getMessage());
        }
    }

    /**
     * laosun支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function laoSun(Request $request)
    {
        try {
            \Log::info('laoSun支付回调：', $request->all());
            $this->onlinePayService->laoSunBack($request->all());
            \Log::info('回调成功处理');
            return $this->responseMessage("SUCCESS");

        } catch (\Exception $exception) {
            \Log::info('laoSun支付回调失败：' . $exception->getMessage());
            return $this->responseException($exception);
        }

    }

    /**
     * fpay支付回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fPayCallBack(Request $request)
    {
        try {
            \Log::info('paytmCash支付回调：', $request->all());
            FPayTHBService::make()->callback($request->all());
            \Log::info('回调成功处理');
            return $this->responseMessage("SUCCESS");
        } catch (\Exception $exception) {
            \Log::alert('paytmCash支付回调执行失败：' . $exception->getMessage());
            return $this->responseException($exception);
        }
    }

    /**
     * paytmCash充值回调
     * @group 第三方接口回调-back
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paytmCash(Request $request)
    {
        try {
            \Log::info('paytmCash支付回调：', $request->all());
            $this->onlinePayService->paytmCashPayInBack($request->all());
            \Log::info('回调成功处理');
            return $this->responseMessage("SUCCESS");
        } catch (\Exception $exception) {
            \Log::alert('paytmCash支付回调执行失败：' . $exception->getMessage());
            return $this->responseException($exception);
        }
    }

    /**
     * paytmCash代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     */
    public function paytmCashPayOutBack(Request $request)
    {
        try {
            \Log::info('paytmCash代付回调：', $request->all());
            $this->onlinePayService->paytmCashPayOutBack($request->all());
            \Log::info('回调成功处理');
            return $this->responseMessage("SUCCESS");
        } catch (\Exception $exception) {
            \Log::alert('paytmCash代付回调调执行失败：' . $exception->getMessage());
            return $this->responseException($exception);
        }
    }


    /**
     * IPayIndianPayIn 充值回调
     * @group 第三方接口回调-back
     * @param Request $request
     */
    public function IPayIndianPayIn(Request $request)
    {
        try {
            \Log::info('IPayIndianPayIn 充值回调：', $request->all());
            IPayIndianService::make()->payInBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::alert('IPayIndianPayIn 充值回调执行失败：' . $exception->getMessage());
            return "error";
        }
    }

    /**
     * IPayIndianPayIn 代付回调
     * @group 第三方接口回调-back
     * @param Request $request
     */
    public function IPayIndianPayOut(Request $request)
    {
        try {
            \Log::info('IPayIndianPayIn 代付回调：', $request->all());
            IPayIndianService::make()->payOutBack($request->all());
            \Log::info('回调成功处理');
            return "success";
        } catch (\Exception $exception) {
            \Log::alert('IPayIndianPayIn 代付回调执行失败：' . $exception->getMessage());
            return "error";
        }
    }


}
