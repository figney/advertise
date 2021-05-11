<?php

namespace App\Admin\Actions\Grid;

use App\Enums\PlatformType;
use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Services\Pay\FPayTHBService;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class SyncBankList extends RowAction
{

    protected $title = '同步银行列表';


    public function handle(Request $request)
    {
        $id = (int)$this->getKey();

        $rc = RechargeChannel::query()->find($id);

        if ($rc->slug === PlatformType::FPay) {
            $list = FPayTHBService::make()->withConfig($rc)->bank_list();
            foreach ($list as $item) {
                RechargeChannelList::query()->firstOrCreate(
                    [
                        'bank_code' => $item['id'],
                        'recharge_channel_id' => $rc->id

                    ],
                    [
                        'bank_name' => $item['bank_name'],
                        'name' => $item['bank_name'],
                    ]
                );
            }
            return $this->response()->message('更新成功')->refresh();
        }
        return $this->response()->error('不支持');

    }

}
