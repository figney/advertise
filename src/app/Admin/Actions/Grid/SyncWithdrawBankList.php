<?php

namespace App\Admin\Actions\Grid;

use App\Enums\PlatformType;
use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\WithdrawChannel;
use App\Models\WithdrawChannelList;
use App\Services\Pay\BananaPayService;
use App\Services\Pay\FPayTHBService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SyncWithdrawBankList extends RowAction
{

    protected $title = '同步银行列表';


    public function handle(Request $request)
    {
        $id = (int)$this->getKey();

        $wc = WithdrawChannel::query()->find($id);


        if ($wc->slug == PlatformType::FPay) {
            $list = FPayTHBService::make()->withConfigWithdraw($wc)->withdraw_bank_list();

            foreach ($list as $item) {
                WithdrawChannelList::query()->firstOrCreate(
                    [
                        'bank_code' => $item['id'],
                        'withdraw_channel_id' => $wc->id

                    ],
                    [
                        'bank_name' => $item['bank_name'],
                        'name' => $item['bank_name'],
                        'input_config' => []
                    ]
                );
            }
            return $this->response()->message('更新成功')->refresh();
        }
        if ($wc->slug === PlatformType::JstPay) {
            $arr = [
                1548 => 'VIB',
                1549 => 'VPBank',
                2001 => 'BIDV',
                2002 => 'VietinBank',
                2003 => 'SHB',
                2004 => 'ABBANK',
                2005 => 'AGRIBANK',
                2006 => 'Vietcombank',
                2007 => 'Techcom',
                2008 => 'ACB',
                2009 => 'SCB',
                2011 => 'MBBANK',
                2012 => 'EIB',
                2020 => 'STB',
                2031 => 'DongABank',
                2032 => 'GPBank',
                2033 => 'Saigonbank',
                2034 => 'PG Bank',
                2035 => 'Oceanbank',
                2036 => 'NamABank',
                2037 => 'TPB',
                2038 => 'HDB',
                2039 => 'VAB',
            ];
            foreach ($arr as $key => $item) {
                WithdrawChannelList::query()->firstOrCreate(
                    [
                        'bank_code' => $key,
                        'withdraw_channel_id' => $wc->id

                    ],
                    [
                        'bank_name' => $item,
                        'name' => $item,
                        'min_money' => 2000000,
                        'max_money' => 200000000,
                        'input_config' => [
                            [
                                'name' => 'bank_account',
                                'slug' => 'BANK_ACCOUNT',
                                'desc' => '收款人开户姓名',
                            ],
                            [
                                'name' => 'bank_no',
                                'slug' => 'BANK_NO',
                                'desc' => '收款人银行帐号',
                            ]
                        ]
                    ]
                );
            }
            return $this->response()->message('更新成功')->refresh();
        }
        if ($wc->slug === PlatformType::Yudrsu) {
            $json = '[{"code": "AGR", "name": "AGRIBANK", "status": 1}, {"code": "BAB", "name": "BAC A BANK", "status": 1}, {"code": "BVB", "name": "BAO VIET BANK", "status": 1}, {"code": "BIDV", "name": "BIDV BANK", "status": 1}, {"code": "EIB", "name": "EXIMBANK", "status": 1}, {"code": "GPB", "name": "GP BANK", "status": 1}, {"code": "HDB", "name": "HD BANK", "status": 1}, {"code": "HLB", "name": "HONGLEONG BANK", "status": 1}, {"code": "IVB", "name": "INDOVINA BANK", "status": 1}, {"code": "KLB", "name": "KIENLONGBANK", "status": 1}, {"code": "LVB", "name": "LIENVIET BANK", "status": 1}, {"code": "MSB", "name": "MARITIME BANK", "status": 1}, {"code": "MB", "name": "MBBANK", "status": 1}, {"code": "NAB", "name": "NAMA BANK", "status": 1}, {"code": "ACB", "name": "NGAN HANG A CHAU", "status": 1}, {"code": "VRB", "name": "NH LD VIET NGA", "status": 1}, {"code": "CIMB", "name": "NH MTV CIMB", "status": 1}, {"code": "NCB", "name": "NH TMCP QUOC DAN", "status": 1}, {"code": "VCAPB", "name": "NHTMCP BAN VIET", "status": 1}, {"code": "VAB", "name": "Ngân hàng TMCP Việt Á", "status": 1}, {"code": "DAB", "name": "Ngân hàng TMCP Đông Á", "status": 1}, {"code": "YOLO", "name": "Ngân hàng số VPDirect", "status": 1}, {"code": "OJB", "name": "OCEANBANK", "status": 1}, {"code": "PGB", "name": "PGBANK", "status": 1}, {"code": "OCB", "name": "PHUONGDONG BANK", "status": 1}, {"code": "STB", "name": "SACOMBANK", "status": 1}, {"code": "SGB", "name": "SAIGONBANK", "status": 1}, {"code": "SCB", "name": "SCB", "status": 1}, {"code": "SEAB", "name": "SEABANK", "status": 1}, {"code": "SHB", "name": "SHB BANK", "status": 1}, {"code": "SHBVN", "name": "SHINHAN BANK VN", "status": 1}, {"code": "TCB", "name": "TECHCOMBANK", "status": 1}, {"code": "TPB", "name": "TIENPHONG BANK", "status": 1}, {"code": "UOB", "name": "UNITED OVERSEAS BANK", "status": 1}, {"code": "VIB", "name": "VIB BANK", "status": 1}, {"code": "PBVN", "name": "VIDPublic Bank", "status": 1}, {"code": "VIETB", "name": "VIETBANK", "status": 1}, {"code": "VCB", "name": "VIETCOMBANK", "status": 1}, {"code": "CTG", "name": "VIETINBANK", "status": 1}, {"code": "VPB", "name": "VPBANK", "status": 1}, {"code": "WOO", "name": "WOORI BANK", "status": 1}, {"code": "ABB", "name": "ABBANK", "status": 1}]';

            $list = json_decode($json, true);

            foreach ($list as $item) {
                WithdrawChannelList::query()->firstOrCreate(
                    [
                        'bank_code' => $item['code'],
                        'withdraw_channel_id' => $wc->id

                    ],
                    [
                        'bank_name' => $item['name'],
                        'name' => $item['name'],
                        'min_money' => 100000,
                        'max_money' => 100000000,
                        'input_config' => [
                            [
                                'name' => 'acc_no',
                                'slug' => 'ACC_NO',
                                'desc' => '收款账号',
                            ],
                            [
                                'name' => 'acc_name',
                                'slug' => 'ACC_NAME',
                                'desc' => '收款姓名',
                            ]
                        ]
                    ]
                );
            }
            return $this->response()->message('更新成功')->refresh();

        }
        if ($wc->slug === PlatformType::BananaPay) {
            $json = BananaPayService::make()->getBank();
            $list = json_decode($json, true);

            foreach ($list as $item) {
                WithdrawChannelList::query()->firstOrCreate(
                    [
                        'bank_code' => $item['bank_code'],
                        'withdraw_channel_id' => $wc->id

                    ],
                    [
                        'bank_name' => $item['bank_name'],
                        'name' => $item['bank_name'],
                        'min_money' => 100000,
                        'max_money' => 100000000,
                        'input_config' => [
                            [
                                'name' => 'account_no',
                                'slug' => 'ACC_NO',
                                'desc' => '收款账号',
                            ],
                            [
                                'name' => 'account_name',
                                'slug' => 'ACC_NAME',
                                'desc' => '收款姓名',
                            ]
                        ]
                    ]
                );
            }
            return $this->response()->message('更新成功')->refresh();

        }
        return $this->response()->error('不支持');

    }

}
