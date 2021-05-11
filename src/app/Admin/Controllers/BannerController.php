<?php

namespace App\Admin\Controllers;

use App\Models\Banner;
use App\Models\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class BannerController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Banner(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('name');
            $grid->column('cover')->image('', 50, 30);
            $grid->column('title')->localData();
            $grid->column('describe')->localData();
            $grid->column('link_name')->localData();
            $grid->column('order')->orderable();
            $grid->column('status')->switch();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Banner(), function (Form $form) {
            $language = Language::query()->get();

            $form->text('name', '名称')->required();
            $form->image('cover', '默认图片')->autoUpload()->uniqueName()->required();


            $form->embeds('title', '标题', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('describe', '描述', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('image', '图片地址', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('link_name', '链接名称', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->text('link', '链接');

            $form->number('order', '排序');
            $form->switch('status', '状态')->options([true => '启用', false => '禁用']);
        });
    }
}
