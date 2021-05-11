<?php

namespace App\Admin\Renderable;


use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;

use App\Models\WalletLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;

class UserWalletLogTable extends LazyRenderable
{


    public function grid(): Grid
    {
        return Grid::make(new WalletLog(), function (Grid $grid) {
            $grid->model()->with(['mate']);
            $grid->column('id', 'ID')->sortable();
            $grid->column('user_id', '用户');
            $grid->column('wallet_type', '钱包')->display(function ($v) {
                return WalletType::fromValue($v)->description;
            });
            $grid->column('wallet_slug', '分组')->display(function ($v) {
                return WalletLogSlug::fromValue($v)->description;
            });
            $grid->column('action_type', '操作')->display(function ($v) {
                return WalletLogType::fromValue($v)->description;
            });
            $grid->column('before_fee', '变动前余额')->display(fn($v) => ShowMoneyLine($v));
            $grid->column('fee', '变动金额')->sortable()->display(fn($v) => ShowMoneyLine($v));
            $grid->column('target_type', '来源');
            $grid->column('target_id', '来源标识');
            $grid->column('mate.remark', '备注');
            $grid->column('created_at')->sortable();


            $grid->paginate(10);
            $grid->disableActions();

            $user_id = $this->payload['user_id'] ?? null;

            $grid->model()->orderBy('id', 'desc');

            if ($user_id) {
                $grid->model()->where('user_id', (int)$user_id);
            }

            $grid->filter(function (Grid\Filter $filter) {

                $filter->equal('wallet_type','钱包类型')->radio(WalletType::asSelectArray());
                $filter->equal('wallet_slug','分组归档')->select(WalletLogSlug::asSelectArray())->width(4);
                $filter->equal('action_type','操作类型')->select(WalletLogType::asSelectArray())->width(4);
                $filter->like('target_type','操作来源')->width(4);
                $filter->date('created_at')->width(4);
            });

        });
    }
}
