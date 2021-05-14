<?php

namespace App\Admin\Controllers;

use App\Models\Language;
use App\Models\Vip;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class VipController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Vip(), function (Grid $grid) {
            $grid->column('id')->sortable();

            $grid->column('icon')->image('', 50, 50);
            $grid->column('name')->editable();
            $grid->column('level', '等级');
            $grid->column('task_num', '任务数');
            $grid->column('task_num_money_list','套餐价格')->display(function ($v) {
                $html = "";
                foreach ($v as $vv) {
                    $html .="<div>".$vv['day']."天 ".ShowMoneyLine($vv['money'])."</div>";
                }
                return $html;
            });


            $grid->column('updated_at');

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
        return Show::make($id, new Vip(), function (Show $show) {
            $show->field('id');
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
        return Form::make(new Vip(), function (Form $form) {
            $language = Language::query()->get();

            $form->tab('基本信息', function (Form $form) {

                $form->text('name', '名称')->required();
                $form->image('icon', '图标')->uniqueName()->autoUpload()->required()->width(2);
                $form->image('bg_image', '背景图片')->uniqueName()->autoUpload()->width(2);

                $form->number('level', '等级')->default(0)->required()->help('等级不能重复，已开通VIP的用户无法降级');


                $form->number('task_num', '套餐次数')->default(10)->required();

                $form->table('task_num_money_list', '套餐价格', function (Form\NestedForm $form) {
                    $form->number('day', '天数')->required();
                    $form->number('money', '价格')->required();
                });


                $form->number('max_buy_num', '最大叠加次数')->default(0)->width(2)->required()->help('0为不限制');


                $form->table('attrs', '属性', function (Form\NestedForm $form) {
                    $form->text('key', '属性标识')->required()->help('大写加下划线组合')->required();
                    $form->text('value', '属性值')->required();
                    $form->number('order', '排序')->required()->default(0);
                })->help('展示用，不参与计算');


                $form->number('order', '排序');
                $form->switch('status', '启用');

                $form->table('dev_config', '开发配置', function (Form\NestedForm $form) {
                    $form->text('key', '配置标识')->required()->help('大写加下划线组合')->required();
                    $form->text('value', '配置值')->required();
                })->help('必须配置：TASK_NUM')->required();
            });
            $form->tab('多语言信息', function (Form $form) use ($language) {

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
            });

            $form->tab('佣金配置', function (Form $form) use ($language) {
                $form->radio('get_commission_type', '佣金计算方式')->options([0 => '根据自身VIP等级开通金额计算', 1 => '全额计算'])->default(0);

                $form->embeds('son_buy_vip_commission_config', '下级购买VIP佣金配置', function (Form\EmbeddedForm $form) {
                    for ($i = 1; $i <= 10; $i++) {
                        $form->rate("parent_" . $i . "_rate", $i . '级分佣比例')->width(2)->default(0)->required();
                    }
                });
                $form->embeds('son_earnings_commission_config', '下级收益佣金配置', function (Form\EmbeddedForm $form) {
                    for ($i = 1; $i <= 10; $i++) {
                        $form->rate("parent_" . $i . "_rate", $i . '级分佣比例')->width(2)->default(0)->required();
                    }
                });

            });

        });
    }
}
