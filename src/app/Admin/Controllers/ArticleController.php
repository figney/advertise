<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Article;
use App\Enums\ArticleType;
use App\Models\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Str;

class ArticleController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Article(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('type')->using(ArticleType::asArray());
            $grid->column('name');
            $grid->column('slug')->copyable();
            $grid->column('order')->orderable();
            $grid->column('status')->switch();
            $grid->column('updated_at')->sortable();

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
        return Show::make($id, new Article(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('title');
            $show->field('describe');
            $show->field('cover');
            $show->field('content');
            $show->field('order');
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
        return Form::make(new Article(), function (Form $form) {

            $language = Language::query()->get();

            $form->radio('type')->required()->options(ArticleType::asArray());
            $form->text('slug')->required()->help('查询标识')->width(2);
            $form->text('name')->required()->help('用于管理员区分')->width(2);
            $form->image('cover');
            $form->number('order');
            $form->switch('status')->default(true);

            $form->embeds('title', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('describe', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('content', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->editor($lang->slug, $lang->name) : $form->editor($lang->slug, $lang->name)->readOnly()->height(100)->help('当前语言已被禁用，无法配置');
                }
            });

            $form->saving(function (Form $form) {
                $form->slug = Str::upper($form->slug);

                $form->slug = str_replace("-", "_", $form->slug);

            });

        });
    }
}
