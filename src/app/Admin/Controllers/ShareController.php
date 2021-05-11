<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Share;
use App\Models\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ShareController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Share(), function (Grid $grid) {
            $grid->column('id')->sortable()->width(100);
            $grid->column('default_cover', '默认封面')->image('', 50, 50);
            $grid->column('name');
            $grid->column('title')->localData();
            $grid->column('describe')->localData();

            $grid->column('status', '启用')->switch();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });


            $grid->disableRowSelector();
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
        return Show::make($id, new Share(), function (Show $show) {
            $show->field('id');
            $show->field('title')->json();
            $show->field('describe')->json();
            $show->field('cover')->json();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Share(), function (Form $form) {

            $language = Language::query()->get();

            $form->text('name')->required();
            $form->image('default_cover', '默认封面')->autoUpload()->uniqueName()->required();


            $form->embeds('title', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('describe', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('cover', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->url($lang->slug, $lang->name)->help('输入图片地址') : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->switch('status')->required();

        });
    }
}
