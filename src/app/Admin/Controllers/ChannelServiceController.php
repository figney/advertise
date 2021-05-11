<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\UpdateKfUserCount;
use App\Admin\Repositories\ChannelService;
use App\Enums\ChannelServiceType;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ChannelServiceController extends AdminController
{
    use Base;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ChannelService(['channel']), function (Grid $grid) {
            if ($this->isChannel()) {
                $grid->model()->whereIn('channel_id', $this->getChannelIds());
            }
            $grid->column('id');
            $grid->column('avatar')->image('', 50, 50);
            $grid->column('channel.name', '渠道');
            $grid->column('type');
            $grid->column('name');
            $grid->column('url');
            $grid->column('user_count', '绑定用户数');
            $grid->column('status')->switch();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            $grid->actions([new UpdateKfUserCount()]);

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
        return Show::make($id, new ChannelService(), function (Show $show) {
            $show->field('id');
            $show->field('channel_id');
            $show->field('type');
            $show->field('name');
            $show->field('avatar');
            $show->field('url');
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
        return Form::make(new ChannelService(), function (Form $form) {

            if ($this->isChannel()) {
                $form->hidden('channel_id', '渠道')->default($this->getChannelID());
            } else {
                $form->select('channel_id', '渠道')->width(2)->required()->options(Channel::options());
            }

            $form->radio('type')->required()->options(ChannelServiceType::asArray());
            $form->text('name')->required()->width(4);
            $form->image('avatar')->width(2)->uniqueName()->autoUpload();
            $form->text('url')->required();
            $form->switch('status')->default(true);

        });
    }
}
