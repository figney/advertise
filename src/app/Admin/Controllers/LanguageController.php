<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Str;

class LanguageController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Language(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('icon')->image('', 50, 50);
            $grid->column('name');
            $grid->column('value');
            $grid->column('slug');
            $grid->column('order');
            $grid->column('status', '启用')->switch();
            $grid->column('show', '后台显示')->switch();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            //$grid->disableDeleteButton();
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
        return Show::make($id, new Language(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('value');
            $show->field('slug');
            $show->field('order');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Language(), function (Form $form) {

            $form->image('icon','图标')->autoUpload()->uniqueName()->required();
            $form->text('name');
            $form->text('value');
            $form->text('slug');
            $form->text('order');
            $form->switch('status');
            $form->switch('show');

            $form->saving(function (Form $form) {
                if ($form->slug) {
                    $form->slug = Str::upper($form->slug);
                }

            });

        });
    }
}
