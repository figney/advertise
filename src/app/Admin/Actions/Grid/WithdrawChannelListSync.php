<?php

namespace App\Admin\Actions\Grid;

use App\Models\WithdrawChannelList;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class WithdrawChannelListSync extends RowAction
{
    /**
     * @return string
     */
    protected $title = '同步到其他选项';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $item_id = $this->getKey();

        $obj = WithdrawChannelList::query()->find($item_id);

        $obj->withdrawChannel->channelList()->update([
            'min_money' => $obj->min_money,
            'max_money' => $obj->max_money,
            'input_config' => $obj->input_config,
        ]);

        return $this->response()
            ->success('同步完成')
            ->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['是否同步最大、最小、表单到其他选项?'];
    }

}
