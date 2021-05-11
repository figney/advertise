<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\CloneLanguageConfig;
use App\Enums\LanguageConfigType;
use App\Models\Language;

use App\Models\LanguageConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LanguageConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(LanguageConfig::query()->disableModelCaching(), function (Grid $grid) {
            $grid->setKeyName("slug");

            $local = request('local', 'EN');
            $grid->column('slug')->width(300);
            $grid->column('name')->width(300);
            $grid->column('type')->width(100)->using(LanguageConfigType::asSelectArray())->filter();
            $grid->column('group', '分组')->width(180)->filter();
            $grid->column('content')->sortable('content->' . $local)->toArray();

            $grid->filter(function (Grid\Filter $filter) use ($local) {
                $filter->panel();
                $filter->like('slug')->width(2);

                $filter->equal('group')->width(2)->select(LanguageConfig::query()->groupBy('group')->pluck('group', 'group'));

                $filter->where('local', function () {

                }, '语言')->select(Language::query()->where('status', true)->pluck('name', 'slug'))->width(2);

                $filter->where('content', function ($q) {
                    $q->where('content', 'like', "%$this->input%");
                })->width(2);

                $filter->where('content_2', function ($q) {
                    /**@var Builder $q */
                    $local = $this->input;
                    //if ($local) $q->whereRaw("length(content->'$.$local') != char_length(content->'$.$local')");
                    if ($local) $q->whereRaw("content->'$.$local' NOT REGEXP '[u0391-uFFE5]'");
                }, '未翻译')->select(Language::query()->where('status', true)->pluck('name', 'slug'))->width(2);


            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new CloneLanguageConfig());
            });


            $grid->quickSearch(['name', 'slug'])->auto(false);

            $grid->disableRowSelector();
            $grid->disableEditButton();
            $grid->disableViewButton();

            $grid->enableDialogCreate();
            $grid->showQuickEditButton();

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
        return Show::make($id, new LanguageConfig(), function (Show $show) {
            $show->field('name');
            $show->field('slug');

            $show->field('grupo');
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


        return Form::make(new LanguageConfig(), function (Form $form) {

            $form->radio('type')->options(LanguageConfigType::asSelectArray())->required();

            $form->text('name')->required();

            $form->isCreating() ? $form->text('slug')->required()->help("会自动转为大写") : $form->hidden('slug');

            $form->text('group', '分组')->options(\App\Models\LanguageConfig::AllGroup())->required()->help("方便使用分组查询");
            $form->embeds('content', '内容', function (Form\EmbeddedForm $form) {

                foreach (Language::query()->get() as $lang) {
                    $form->textarea($lang->slug, $lang->name)->help("可以使用{0}来替换变量")->required();
                }

            });

            $form->saving(function (Form $form) {
                $form->slug = Str::upper($form->slug);

                $form->slug = str_replace("-", "_", $form->slug);

            });

        });
    }
}
