<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Sms;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SmsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Sms(), function (Grid $grid) {

            $grid->model()->orderBy('created_at', 'desc');

            $grid->column('national_number');
            $grid->column('country_code');
            $grid->column('code')->copyable();
            $grid->column('ip');
            $grid->column('local');
            $grid->column('lang');
            $grid->column('user_id');

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
        return Show::make($id, new Sms(), function (Show $show) {
            $show->field('id');
            $show->field('country_calling_code');
            $show->field('country_code');
            $show->field('ip');
            $show->field('national_number');
            $show->field('status');
            $show->field('type');
            $show->field('verification_code');
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
        return Form::make(new Sms(), function (Form $form) {
            $form->display('id');
            $form->text('country_calling_code');
            $form->text('country_code');
            $form->text('ip');
            $form->text('national_number');
            $form->text('status');
            $form->text('type');
            $form->text('verification_code');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
