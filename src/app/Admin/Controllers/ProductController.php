<?php

namespace App\Admin\Controllers;

use App\Enums\ProductType;
use App\Enums\WalletType;
use App\Models\Language;
use App\Models\Product;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ProductController extends AdminController
{

    protected $title = "产品";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->model()->orderBy('order', 'desc');
            $grid->column('id');
            $grid->column('cover')->image('', 100, 50);
            $grid->column('type')->using(ProductType::asSelectArray());
            $grid->column('name')->sortable();
            $grid->column('day_rate')->sortable();
            $grid->column('day_cycle')->sortable()->editable();
            $grid->column('min_money')->sortable()->money();
            $grid->column('user_max_amount', '用户最大投资额')->sortable()->money();
            $grid->column('user_max_buy', '最大购买次数')->sortable()->editable();
            $grid->column('is_no_buy_user', '只允许未购买用户')->bool();
            $grid->column('all_amount', '总规模')->sortable()->money();
            $grid->column('order')->editable();
            $grid->column('status', '状态')->switch();
            $grid->column('is_commission', '是否分佣')->bool();


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
        return Show::make($id, new Product(), function (Show $show) {
            $show->field('id');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Product(), function (Form $form) {
            $language = Language::query()->get();

            $form->column(6, function (Form $form) {


                $form->isCreating() ? $form->radio('type', '投资类型')
                    ->required()
                    ->options(ProductType::asSelectArray())
                    ->default(ProductType::balance)
                    ->help('赠送金无法购买投资产品') : $form->display('type')->customFormat(function ($v) {
                    return ProductType::fromValue($v)->description;
                });
                $form->text('order')->required()->width(4)->help('越大越靠前');

                $form->switch('is_number_buy', '数量购买模式')->default(1)->help('适用于单价x数量类型产品');
                $form->text('min_money', '起投金额/产品单价')->required()->width(4);
                $form->text('all_amount', '总规模')->required()->width(4);

                $form->text('user_max_amount', '用户最大投资额')->default(0)->required()->width(4)->help('0不限制');
                $form->text('user_max_buy', '用户最大购买次数')->default(0)->required()->width(4)->help('0不限制');


                $form->switch('is_no_buy_user', '只允许未购买用户')->help('开启后，只有购买过任意一款产品的用户将无法购买');

                $form->text('name', '产品名称')->help('用于区分每个产品')->required();

                $form->rate('day_rate', '日回报率')->required();

                $form->number('day_cycle', '周期（天）')->min(1)->required();

                $form->switch('is_day_account', '每日结算')->help('是否每日结算利息');
                $form->switch('status', '状态');

                $form->table('attrs', '产品属性', function (Form\NestedForm $form) {
                    $form->text('slug', '属性语言标识')->required();
                    $form->text('value', '属性值')->required();
                    $form->text('remark', '说明')->required();
                });


                $form->list('select_money_list', '金额/数量选项')->default([100, 500])->max(6)->width(4)->required()->help('最多支持六个选项');

                $form->divider();


                $form->switch('is_commission', '是否分佣')->help('开启后可给上级分佣');

                $form->embeds('commission_config', '佣金配置', function (Form\EmbeddedForm $form) {
                    for ($i = 1; $i <= 10; $i++) {
                        $form->rate("parent_".$i."_rate", $i . '级分佣比例')->width(2)->default(0)->required();
                    }
                });


            });


            $form->column(6, function (Form $form) use ($language) {
                $form->image('cover', '产品封面')->autoUpload()->uniqueName()->required()->help('图片尺寸：724x256');
                $form->image('big_cover', '产品详情大图')->autoUpload()->uniqueName()->required()->help('图片尺寸：750x604');

                $form->embeds('title', '产品标题', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                    }
                });

                $form->embeds('describe', '产品描述', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                    }
                });

                $form->embeds('content', '产品详情', function (Form\EmbeddedForm $form) use ($language) {
                    foreach ($language as $lang) {
                        $lang->show ? $form->editor($lang->slug, $lang->name) : $form->editor($lang->slug, $lang->name)->readOnly()->height(100)->help('当前语言已被禁用，无法配置');
                    }
                });

            });

        });
    }
}
