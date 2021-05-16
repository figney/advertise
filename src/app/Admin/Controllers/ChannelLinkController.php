<?php

namespace App\Admin\Controllers;

use App\Models\Channel;
use App\Models\ChannelLink;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChannelLinkController extends AdminController
{
    use Base;

    protected $title = "渠道推广链接";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ChannelLink(), function (Grid $grid) {

            if ($this->isChannel()) {
                $grid->model()->whereIn('channel_id', $this->getChannelIds());
            }

            $grid->model()->with(['channel', 'channelService']);

            $grid->column('id')->sortable();

            $grid->column('name');
            $grid->column('channel.name', '所属渠道');
            $grid->column('channelService.name', '绑定客服');

            $grid->column('address', '推广地址')->display(function () {

                $addressList = $this->channel?->address();
                if (!$addressList || count($addressList) <= 0) return "<span class='text-danger'>渠道未绑定域名，请联系管理员</span>";

                $html = "";
                foreach ($addressList as $address) {
                    $html .= $address . "&cl=" . $this->id;
                }
                return $html;
            })->qrcode();
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
        return Show::make($id, new ChannelLink(), function (Show $show) {
            $show->field('id');
            $show->field('channel_id');
            $show->field('name');
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
        return Form::make(new ChannelLink(), function (Form $form) {
            if ($this->isChannel()) {
                $form->hidden('channel_id', '渠道')->default($this->getChannelID());
            } else {
                $form->select('channel_id', '渠道')->width(2)->required()->options(Channel::options());
            }


            $form->select('channel_service_id', '绑定客服')->default(0)->width(2)->options($this->getChannelServiceModel()->pluck('name', 'id')->prepend('默认', 0))->required();


            $form->text('name', '推广链接名称')->width(2)->required();
            $form->switch('status')->default(1);

        });
    }
}
