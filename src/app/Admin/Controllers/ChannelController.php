<?php

namespace App\Admin\Controllers;


use App\Models\AdminUser;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChannelController extends AdminController
{
    use Base;

    protected $title = "渠道";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {


        return Grid::make(new Channel(), function (Grid $grid) {

            if ($this->isChannel()) {
                $grid->model()->whereIn('id', $this->getChannelIds());
            }

            $grid->model()->with(['admin']);

            $grid->column('id')->sortable();
            $grid->column('admin.name', '绑定管理员');
            $grid->column('name');
            $grid->column('parent_id', '上级ID');

            $grid->column('addressList', '推广地址')->display(function () {

                $addressList = $this->address();
                if (count($addressList) <= 0) return "<span class='text-danger'>渠道未绑定域名，请联系管理员</span>";

                return "<span class='text-success'>域名已绑定，请创建推广链接</span>";
            });

            $grid->column('created_at');
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
            });

            if (!$this->isAdministrator()) {
                $grid->disableEditButton();
                $grid->disableDeleteButton();
                $grid->disableCreateButton();
            }

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
        return Show::make($id, new Channel(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('parent_id');
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
        return Form::make(new Channel(), function (Form $form) {

            $form->select('admin_id', '绑定用户')->options(AdminUser::query()->pluck('name', 'id'))->width(2)->help('需要先创建渠道用户');

            $form->select('parent_id', '父渠道')->default(0)->options(\App\Models\Channel::query()->where('parent_id', 0)->pluck('name', 'id')->prepend('无', 0))->width(2);

            $form->text('name')->width(2)->required();

        });
    }
}
