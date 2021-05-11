<?php

namespace App\Admin\Actions\Grid;

use App\Models\ChannelService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class UpdateKfUserCount extends RowAction
{
    /**
     * @return string
     */
    protected $title = '更新绑定人数';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $kf = ChannelService::query()->find($this->getKey());

        $kf->updateUserCount();

        return $this->response()->refresh()->success('更新成功');
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定要更新吗?', '虽然这个提示没是没用'];
    }


}
