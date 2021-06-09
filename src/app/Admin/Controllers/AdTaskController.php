<?php

namespace App\Admin\Controllers;

use App\Models\AdTask;
use App\Models\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AdTaskController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AdTask(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('vip_level')->sortable();
            $grid->column('money')->display(fn($v) => ShowMoneyLine($v))->sortable();
            $grid->column('complete_click_number')->sortable();
            $grid->column('total')->sortable();
            $grid->column('rest', '已接数量')->sortable();
            $grid->column('complete_num', '完成数量')->sortable();
            $grid->column('valid_hour')->sortable();
            $grid->column('overdue_return')->bool();
            $grid->column('vip_level_max_config')->display(function ($v) {
                $html = "";
                collect($v)->each(function ($item) use (&$html) {
                    $html .= "<div>{$item['level']}级，每日:{$item['day_max']}，总:{$item['max']}</div>";
                });

                return $html;
            });
            $grid->column('tags')->label();
            $grid->column('order')->editable()->sortable();
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('vip_level')->width(2);
                $filter->ngt('rest','已接数量')->width(2);
                $filter->ngt('complete_num','完成数量')->width(2);

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
        return Show::make($id, new AdTask(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(AdTask::with(['adData']), function (Form $form) {

            $language = Language::query()->get();

            $form->tab('基本信息', function (Form $form) {
                $form->text('name', '名称')->required();

                $form->number('vip_level', 'VIP等级限制')->help('0代表非会员也能完成')->required();

                $form->number('money', '完成一次收益')->required()->width(2)->default(0);

                $form->number('complete_click_number', '完成点击次数')->required();

                $form->number('total', '任务总数')->required();
                $form->text('rest', '已接数量')->default(0)->disable()->width(2);
                $form->number('valid_hour', '有效时间（小时）')->required();

                $form->switch('overdue_return', '过期返还次数')->help('过期后未完成返还单日总次数')->default(0);

                $form->table('vip_level_max_config', 'VIP等级数量设置', function (Form\NestedForm $form) {
                    $form->number('level', 'VIP等级')->default(0)->required();
                    $form->number('day_max', '每天可完成次数')->default(0)->required();
                    $form->number('max', '最多可完成次数')->default(0)->required();
                });

                $form->checkbox('tags', '标签')->options([
                    'hot' => '热门',
                    'recommend' => '推荐',
                ]);

                $form->number('order');

            });


            $form->tab('分享内容', function (Form $form) use ($language) {
                $form->image('adData.share_image', '分享图片')->autoUpload()->uniqueName()->compress([
                    'width' => 600,
                    'height' => 400,
                    'quality' => 90,
                    'crop' => true,
                ])->help('600x400');
                $form->embeds('adData.share_content', '分享文案', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->textarea($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                    }
                });
            });

            $form->tab('佣金配置', function (Form $form) {
                $form->radio('has_commission', '是否给上级发放佣金')->options([1 => '发放', 0 => '不发放'])->default(1)->when(1, function (Form $form) {

                    $form->rate('level_decrease', 'VIP等级递减比例')->width(5)->default(80)->required()->help('当上级VIP等级低于下级完成广告VIP等级时，只能获得部分佣金，计算公式为 (递减比例 / 等级差) x 广告奖励 x 分佣比例 ');

                    $form->embeds('commission_config', '完成任务给上级分佣配置', function (Form\EmbeddedForm $form) {
                        for ($i = 1; $i <= 10; $i++) {
                            $form->rate("parent_" . $i . "_rate", $i . '级分佣比例')->width(2)->default(0)->required();
                        }
                    });
                });
            });

            $form->tab('广告内容', function (Form $form) use ($language) {

                $form->image('icon', '任务图标')->autoUpload()->uniqueName()->required()->width(2)->compress([
                    'width' => 200,
                    'height' => 200,
                    'quality' => 90,
                    'crop' => true,
                ])->help('200x200');

                $form->embeds('adData.title', '标题', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->text($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                    }
                });
                $form->embeds('adData.describe', '描述', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->text($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                    }
                });
                $form->embeds('adData.content', '广告内容', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->editor($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                    }
                });
            });
        });
    }
}
