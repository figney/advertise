<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\UserWallet;
use App\Admin\Actions\Grid\UserWalletLogAction;
use App\Models\MoneyBao;
use Carbon\Carbon;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class MoneyBaoController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MoneyBao(), function (Grid $grid) {

            $grid->model()->orderBy('user_id', 'desc');

            $grid->column('user_id');
            //$grid->column('balance', '余额存款')->sortable()->money();
            //$grid->column('usdt_balance', 'USDT存款')->sortable()->money(true);
            $grid->column('give_balance', '赠送金存款')->sortable()->money();

           // $grid->column('balance_interest', '余额总利息')->sortable()->money();
           // $grid->column('balance_earnings', '余额总收益')->sortable()->money();


            //$grid->column('usdt_balance_interest', 'USDT总利息')->sortable()->money(true);
           // $grid->column('usdt_balance_earnings', 'USDT总收益')->sortable()->money(true);


            $grid->column('give_balance_earnings', '赠送金总收益')->sortable()->money();

            //$grid->column('has')->bool();
            $grid->column('last_grant_time', '最后一次发放时间');
            $grid->column('last_time', '最后存入时间');
            $grid->column('give_status', '状态');


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(2);

            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new UserWalletLogAction($actions->row->user_id));
                if (Permission::check("user-wallet-set")) $actions->append(new UserWallet($actions->row->user_id));
            });

            $grid->disableCreateButton();
            //$grid->disableEditButton();
            $grid->disableDeleteButton();

        });
    }

    protected function form()
    {
        return Form::make(new MoneyBao(), function (Form $form) {
            $form->text('balance');
            $form->text('usdt_balance');
            $form->text('give_balance');
            $form->text('give_balance_earnings');
        });
    }

}
