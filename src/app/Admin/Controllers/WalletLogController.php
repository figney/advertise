<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\WalletLog;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class WalletLogController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new WalletLog(), function (Grid $grid) {
            $grid->model()->with(['mate']);
            $grid->column('id', 'ID')->sortable();
            $grid->column('user_id', '用户')->filter();
            $grid->column('wallet_type', '钱包')->display(function ($v) {
                return WalletType::fromValue($v)->description;
            })->filter();
            $grid->column('wallet_slug', '分组')->display(function ($v) {
                return WalletLogSlug::fromValue($v)->description;
            })->filter();
            $grid->column('action_type', '操作')->display(function ($v) {
                return WalletLogType::fromValue($v)->description;
            })->filter();
            $grid->column('before_fee', '变动前余额')->display(fn($v) => (float)$v);
            $grid->column('fee', '变动金额')->sortable()->display(fn($v) => (float)$v);
            $grid->column('target_type', '来源');
            $grid->column('target_id', '来源标识');
            $grid->column('mate.remark', '备注');
            $grid->column('created_at')->sortable();


            $grid->paginate(10);
            $grid->disableActions();

            $user_id = $this->payload['user_id'] ?? null;

            $grid->model()->orderBy('created_at', 'desc');

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

            $grid->disableDeleteButton();
            $grid->disableCreateButton();
            $grid->disableEditButton();
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new WalletLog(), function (Show $show) {
            $show->field('id');
            $show->field('channel_id');
            $show->field('user_id');
            $show->field('wallet_id');
            $show->field('wallet_type');
            $show->field('action_type');
            $show->field('fee');
            $show->field('target_type');
            $show->field('target_id');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new WalletLog(), function (Form $form) {
            $form->display('id');
            $form->text('channel_id');
            $form->text('user_id');
            $form->text('wallet_id');
            $form->text('wallet_type');
            $form->text('action_type');
            $form->text('fee');
            $form->text('target_type');
            $form->text('target_id');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
