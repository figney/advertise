<?php

namespace App\Admin\Controllers;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\UserProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserProductController extends AdminController
{
    use Base;
    protected $title = "已投资产品";
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserProduct(), function (Grid $grid) {

            $grid->model()->with(['product'])->orderBy('id', 'desc');

            if (!$this->isAdministrator()) {
                $grid->model()->byChannel();
            }

            $grid->column('id')->sortable();
            $grid->column('channel_id')->filter();
            $grid->column('link_id')->filter();
            $grid->column('user_id')->filter();
            $grid->column('product.name','产品名称');
            $grid->column('product_type')->using(ProductType::asSelectArray());
            $grid->column('day_cycle');
            $grid->column('day_rate');
            $grid->column('is_day_account')->bool();
            $grid->column('last_grant_time')->display(function ($val) {
                return $this->interest_count > 0 ? $val : '-';
            });
            $grid->column('next_grant_time');
            $grid->column('amount')->money();
            $grid->column('over_day');
            $grid->column('is_over')->bool();
            $grid->column('over_time');
            $grid->column('interest')->money();

            $grid->column('interest_count');
            $grid->column('created_at');
            $grid->column('status')->bool();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id')->width(2);
                $filter->equal('user_id')->width(2);

                $filter->equal('product_id')->select(Product::query()->orderByDesc('order')->pluck('name','id'))->width(3);

            });

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();

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
        return Show::make($id, new UserProduct(), function (Show $show) {
            $show->field('id');
            $show->field('channel_id');
            $show->field('link_id');
            $show->field('user_id');
            $show->field('product_id');
            $show->field('product_type');
            $show->field('day_cycle');
            $show->field('day_rate');
            $show->field('is_day_account');
            $show->field('last_grant_time');
            $show->field('next_grant_time');
            $show->field('amount');
            $show->field('over_day');
            $show->field('is_over');
            $show->field('over_time');
            $show->field('buy_log_id');
            $show->field('over_log_id');
            $show->field('interest');
            $show->field('status');
            $show->field('interest_count');
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
        return Form::make(new UserProduct(), function (Form $form) {
            $form->display('id');
            $form->text('channel_id');
            $form->text('link_id');
            $form->text('user_id');
            $form->text('product_id');
            $form->text('product_type');
            $form->text('day_cycle');
            $form->text('day_rate');
            $form->text('is_day_account');
            $form->text('last_grant_time');
            $form->text('next_grant_time');
            $form->text('amount');
            $form->text('over_day');
            $form->text('is_over');
            $form->text('over_time');
            $form->text('buy_log_id');
            $form->text('over_log_id');
            $form->text('interest');
            $form->text('status');
            $form->text('interest_count');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
