<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Domain;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class DomainController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Domain(['channel']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('channel.name','渠道');
            $grid->column('domain');
            $grid->column('type');
            $grid->column('status')->switch();
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
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
        return Show::make($id, new Domain(), function (Show $show) {
            $show->field('id');
            $show->field('channel_id');
            $show->field('domain');
            $show->field('type');
            $show->field('status');
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
        return Form::make(new Domain(), function (Form $form) {
            $form->display('id');
            $form->select('channel_id')->options(Channel::query()->pluck('name','id'))->required()->width(2);
            $form->text('domain');
            $form->radio('type')->options([
                'share' => '分享',
                'ad' => '广告'
            ])->required();
            $form->switch('status')->default(true);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
