<?php

namespace App\Admin\Controllers;

use App\Enums\UserAdTaskType;
use App\Models\UserAdTask;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserAdTaskController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserAdTask::query()->with(['adTask', 'user', 'user.wallet', 'user.walletCount', 'user.withdrawOrdersChecking', 'user.vips']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user')->userInfo();
            $grid->column('adTask.name');
            $grid->column('channel_id');
            $grid->column('link_id');

            $grid->column('expired_time');
            $grid->column('finished_time');


            $grid->column('money')->display(fn($v) => ShowMoneyLine($v));
            $grid->column('complete_click_number');
            $grid->column('now_click_number');
            $grid->column('overdue_return')->bool();
            $grid->column('status')->label([
                UserAdTaskType::Finished => 'success',
                UserAdTaskType::InProgress => 'info',
                UserAdTaskType::HasExpired => 'danger',
            ]);
            $grid->column('imei');
            $grid->column('ip');
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('channel_id')->width(2);
                $filter->equal('link_id')->width(2);
                $filter->equal('user_id')->width(2);
                $filter->equal('ad_task_id')->width(2);
                $filter->equal('level')->width(2);
                $filter->equal('status')->select(UserAdTaskType::asSelectArray())->width(2);

                $filter->date('created_at')->width(2);
                $filter->date('expired_time')->width(2);
                $filter->date('finished_time')->width(2);
                $filter->gt('now_click_number')->width(2);

            });


            $grid->disableCreateButton();
            $grid->disableDeleteButton();
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
        return Show::make($id, new UserAdTask(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserAdTask(), function (Form $form) {

        });
    }
}
