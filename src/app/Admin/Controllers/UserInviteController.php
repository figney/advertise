<?php

namespace App\Admin\Controllers;

use App\Models\UserInvite;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserInviteController extends AdminController
{
    protected $title = "邀请";

    protected function grid()
    {
        return Grid::make(new UserInvite(), function (Grid $grid) {

            $grid->model()->orderBy('total_all', 'desc');

            $grid->column('user_id');
            /*$grid->column('activity');
            $grid->column('balance_earnings');
            $grid->column('balance_recharge');
            $grid->column('balance_withdraw');*/
            $grid->column('channel_id');
            /*$grid->column('invite_id_1');
            $grid->column('invite_id_10');
            $grid->column('invite_id_2');
            $grid->column('invite_id_3');
            $grid->column('invite_id_4');
            $grid->column('invite_id_5');
            $grid->column('invite_id_6');
            $grid->column('invite_id_7');
            $grid->column('invite_id_8');
            $grid->column('invite_id_9');*/
            $grid->column('level');
            $grid->column('link_id');
            $grid->column('total_all')->sortable();
            $grid->column('total_1')->sortable();
            $grid->column('total_2')->sortable();
            $grid->column('total_3')->sortable();
            $grid->column('total_4')->sortable();
            $grid->column('total_5')->sortable();
            $grid->column('total_6')->sortable();
            $grid->column('total_7')->sortable();
            $grid->column('total_8')->sortable();
            $grid->column('total_9')->sortable();
            $grid->column('total_10')->sortable();
            /*$grid->column('usdt_balance_earnings');
            $grid->column('usdt_balance_recharge');
            $grid->column('usdt_balance_withdraw');*/
            $grid->column('recharge_count')->sortable();
            $grid->column('withdraw_count')->sortable();
            $grid->column('created_at','注册时间');
            /*$grid->column('updated_at')->sortable();*/

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(2);

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
        return Show::make($id, new UserInvite(), function (Show $show) {
            $show->field('id');
            $show->field('activity');
            $show->field('balance_earnings');
            $show->field('balance_recharge');
            $show->field('balance_withdraw');
            $show->field('channel_id');
            $show->field('invite_id_1');
            $show->field('invite_id_10');
            $show->field('invite_id_2');
            $show->field('invite_id_3');
            $show->field('invite_id_4');
            $show->field('invite_id_5');
            $show->field('invite_id_6');
            $show->field('invite_id_7');
            $show->field('invite_id_8');
            $show->field('invite_id_9');
            $show->field('level');
            $show->field('link_id');
            $show->field('recharge_count');
            $show->field('total_1');
            $show->field('total_10');
            $show->field('total_2');
            $show->field('total_3');
            $show->field('total_4');
            $show->field('total_5');
            $show->field('total_6');
            $show->field('total_7');
            $show->field('total_8');
            $show->field('total_9');
            $show->field('total_all');
            $show->field('usdt_balance_earnings');
            $show->field('usdt_balance_recharge');
            $show->field('usdt_balance_withdraw');
            $show->field('user_id');
            $show->field('withdraw_count');
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
        return Form::make(new UserInvite(), function (Form $form) {
            $form->display('id');
            $form->text('activity');
            $form->text('balance_earnings');
            $form->text('balance_recharge');
            $form->text('balance_withdraw');
            $form->text('channel_id');
            $form->text('invite_id_1');
            $form->text('invite_id_10');
            $form->text('invite_id_2');
            $form->text('invite_id_3');
            $form->text('invite_id_4');
            $form->text('invite_id_5');
            $form->text('invite_id_6');
            $form->text('invite_id_7');
            $form->text('invite_id_8');
            $form->text('invite_id_9');
            $form->text('level');
            $form->text('link_id');
            $form->text('recharge_count');
            $form->text('total_1');
            $form->text('total_10');
            $form->text('total_2');
            $form->text('total_3');
            $form->text('total_4');
            $form->text('total_5');
            $form->text('total_6');
            $form->text('total_7');
            $form->text('total_8');
            $form->text('total_9');
            $form->text('total_all');
            $form->text('usdt_balance_earnings');
            $form->text('usdt_balance_recharge');
            $form->text('usdt_balance_withdraw');
            $form->text('user_id');
            $form->text('withdraw_count');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
