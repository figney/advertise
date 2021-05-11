<?php

namespace App\Admin\Controllers;

use App\Enums\TaskTargetType;
use App\Enums\UserHookType;
use App\Enums\WalletType;
use App\Models\Language;
use App\Models\Task;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class TaskController extends AdminController
{

    protected $title = "任务";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Task(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('icon')->image('', 50, 50);
            $grid->column('name');

            $grid->column('hook')->using(UserHookType::asSelectArray())->filter();
            $grid->column('task_target')->using(TaskTargetType::asSelectArray())->filter();
            $grid->column('continuous_day')->display(function ($v) {
                if ($v > 0) {
                    return $v . ' 天';
                }
                return "-";
            });
            $grid->column('target_condition', '目标条件')->display(function ($v) {
                if ($v > 0) {
                    return $v . ' 人/次/金额';
                }
                return "-";
            });
            $grid->column('day_max', '每日上限')->display(function ($v) {
                if ($this->task_target == TaskTargetType::Every) {
                    return $v . ' 次';
                }
                return "-";
            });
            $grid->column('start_amount')->display(function ($v) {
                if ($v > 0) return (float)$v . ' ' . Setting('default_currency');

                return "-";
            });
            $grid->column('increase_amount')->display(function ($v) {
                if ($v > 0) return (float)$v . ' ' . Setting('default_currency');

                return "-";
            });
            $grid->column('check_withdraw', '提现检测')->bool()->help('计算累计金额时，会扣除提现金额，可防止用户反复充提刷累计金额');
            $grid->column('repetition')->bool();
            $grid->column('is_user_award')->bool();
            $grid->column('user_award_rate')->display(function ($v) {
                if ($v > 0) return (float)$v . " %";

                return "-";
            });
            $grid->column('user_award_amount')->display(function ($v) {
                if ($v > 0) return (float)$v . ' ' . Setting('default_currency');

                return "-";
            });
            $grid->column('is_parent_award')->bool();
            $grid->column('is_deduct')->bool();
            $grid->column('deduct_rate')->display(function ($v) {
                if (!$this->is_deduct) return "-";
                if ($this->user_award_rate > 0) return (float)$this->user_award_rate . " %";

                return (float)$v . " %";
            });
            $grid->column('auto_get')->bool();
            $grid->column('is_show_alert', '弹窗提醒')->switch();
            $grid->column('total_award_amount')->display(function ($v) {
                if ($v > 0) return (float)$v . ' ' . Setting('default_currency');

                return "-";
            });

            $grid->column('order')->sortable()->editable();
            $grid->column('status')->switch();
            $grid->column('is_show','显示')->switch();
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            if (!\Admin::user()->isAdministrator()) {
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
        return Show::make($id, new Task(), function (Show $show) {
            $show->field('id');
        });
    }

    protected function form()
    {
        return Form::make(new Task(), function (Form $form) {

            $language = Language::query()->get();

            $form->radio('user_type', '用户类型')->options([0 => '通用', 1 => '未充值用户', 2 => '已充值用户'])->default(0)->required();

            //if ($form->isCreating()) {
            $form->radio('hook', '触发事件')
                ->options(collect(UserHookType::asSelectArray())->filter(fn($i, $k) => !in_array($k, [UserHookType::Withdraw, UserHookType::Login]))->toArray())->required();
            $form->radio('task_target', '任务目标')->options(TaskTargetType::asSelectArray())->help('首次只有注册、充值、APP有效')->required();
            /*} else {
                $form->display('hook')->customFormat(fn($v) => UserHookType::fromValue($v)->description);
                $form->display('task_target')->customFormat(fn($v) => TaskTargetType::fromValue($v)->description);
            }*/


            $form->text('continuous_day', '连续时间')->required()->prepend('天')->default(0)->width(2)->help('选择连续N天时有效');
            $form->text('target_condition', '目标条件')->required()->prepend('人 / 次 / 金额')->default(0)->width(2)->help('USDT会根据当时汇率换算成法币参与计算');
            //$form->text('amount_condition', '法币条件')->type('number')->required()->prepend('法币')->default(0)->width(2)->help('充值、收益时有效，0为无条件限制，<span class="text-danger text-bold">法币与USDT为或的关系，有一个满足即可，USDT会根据当时汇率换算成法币参与计算</span>');

            $form->text('start_amount', '初始金额')->required()->prepend('金额')->default(0)->width(2)->help('连续N天递增时有效，0为无条件限制，USDT会自动计算');
            $form->text('increase_amount', '每天递增金额')->type('number')->required()->prepend('金额')->help('连续N天递增时有效')->width(2)->default(0);


            $form->number('day_max', '每天触发次数上限')->default(0)->help('邀请、充值有效，0为不限制次数');

            $form->switch('check_withdraw', '检测提现')->help('开启后，计算累计金额时，会扣除提现金额，可防止用户反复充提刷累计金额');

            $form->switch('repetition', '允许重复完成')->help('不重复则为一次性任务，完成后用户将无法再看到此任务');

            $form->radio('is_user_award', '给自己奖励')->options([1 => '有奖励', 0 => '无奖励'])->default(1)->help('USDT的奖励会以发放奖励时的汇率扣除')->required();;
            $form->rate('user_award_rate', '奖励比例百分比')->required()->default(0)->width(2)->help('当有金额参与计算时有效，可满足金额多少倍奖励的需求，<span class="text-danger text-bold">算法规则为计算金额的倍数，如：用户充值金额。连续任务无效</span>');
            $form->radio('wallet_type', '奖励类型')
                ->options(collect(WalletType::asSelectArray())->filter(fn($i, $k) => $k !== WalletType::usdt)->toArray())
                ->default(WalletType::give)
                ->help('只有固定金额奖励有效');
            $form->text('user_award_amount', '奖励')->required()->default(0)->prepend('金额')->width(2)->help('固定奖励金额');


            $form->radio('is_parent_award', '给上级奖励')->options([1 => '给上级奖励', 0 => '不给上级奖励'])->default(0)->when(1, function (Form $form) {

                for ($i = 1; $i <= 10; $i++) {
                    $i_f = $form->rate('parent_award_rate_' . $i, "$i 级上线奖励比例百分比")->default(0)->width(2);

                    if ($i === 1) $i_f->help('设置0则当前等级的上线无奖励，<span class="text-danger text-bold">算法规则为计算金额的倍数，如果没有计算金额则用用户自己的奖励*比例</span>');
                }

            })->help('USDT的奖励会以发放奖励时的汇率扣除')->required();
            $form->switch('is_deduct', '用户提现扣除')->help('用户提现时，此奖励记录会参与扣除计算');

            $form->rate('deduct_rate', '扣除比例百分比')->required()->default(100)->width(2)->help('当奖励金额是固定时，需要设置，推荐使用奖励金额/目标金额');

            $form->hidden('auto_get', '开启自动发放')->default(1)->help('手动时，需要用户点击领取奖励才会发放，<span class="text-danger text-bold">需要扣除奖励的任务必须自动发放，不管是自己还是上级</span>');

            $form->switch('is_show_alert', '弹出用户领取窗口')->help('强制提现用户此奖励，实现用户手动确认的功能');

            //任务总奖金
            $form->text('total_award_amount', '任务总奖金')->required()->type('number')->default(0)->width(2)->help(' 用于展示，不参加计算');


            $form->dateRange('start_time', 'end_time', '任务有效期')->help('结束时间留空为无限期任务，如果开始时间为空或者小于当前时间，则以任务创建时间为准，开始时间前的数据不会参与计算');

            $form->number('order');
            $form->switch('status')->default(1);
            $form->switch('is_show','是否显示')->default(1);

            $form->text('name', '名称')->width(3)->required();
            $form->image('icon', '任务图标')->autoUpload()->uniqueName()->width(3);

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
            $form->embeds('btn_name', '按钮文字', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->embeds('content', '详细说明', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name)->help('支持HTML') : $form->hidden($lang->slug, $lang->name);
                }
            });


            $form->saving(function (Form $form) {

                $user_award_rate = (float)$form->user_award_rate;
                $user_award_amount = (float)$form->user_award_amount;
                $deduct_rate = (float)$form->deduct_rate;
                $is_deduct = (int)$form->is_deduct;
                $auto_get = (int)$form->auto_get;


                if ($user_award_rate > 0 && $user_award_amount > 0) $form->response()->error('用户奖励比例与固定金额只能设置一个')->alert();

                if ($user_award_amount > 0 && $is_deduct == 1 & $deduct_rate <= 0) $form->response()->error('固定金额奖励扣除必须设置扣除比例')->alert();

                //如果需要扣除，检测提交数据是否符合
                if ($is_deduct == 1 && $auto_get !== 1) return $form->response()->error('需要开启自动发放')->alert();

                if ($form->hook == UserHookType::Register) {
                    if (!in_array($form->task_target, [TaskTargetType::First])) return $form->response()->error('注册只支持【首次】任务目标')->alert();


                }
                //签到
                if ($form->hook == UserHookType::Sign) {
                    if (!in_array($form->task_target, [TaskTargetType::Every, TaskTargetType::Accomplish, TaskTargetType::ContinuousDay,])) return $form->response()->error('签到只支持【每次、累计、连续N天】任务目标')->alert();
                }

                //邀请
                if ($form->hook == UserHookType::Invite) {

                    if (!in_array($form->task_target, [TaskTargetType::Every, TaskTargetType::Accomplish, TaskTargetType::ContinuousDay,])) return $form->response()->error('邀请只支持【每次、累计、连续N天】任务目标')->alert();
                }
                //收益
                if ($form->hook == UserHookType::Earnings) {

                    if (!in_array($form->task_target, [TaskTargetType::Accomplish, TaskTargetType::ContinuousDay])) return $form->response()->error('收益只支持【累计、连续N天】任务目标')->alert();

                    if ($user_award_rate > 0) return $form->response()->error('收益不支持比例奖励形式，须填写固定奖励')->alert();
                }

                if ($form->hook == UserHookType::Recharge) {
                    //if ($is_deduct <= 0) return $form->response()->error('充值奖励必须开启提现扣除')->alert();
                }

                if ($form->task_target == TaskTargetType::Accomplish) {
                    $target_condition = (float)$form->target_condition;
                    if ($target_condition <= 0) return $form->response()->error('请设置累计/到达的目标条件')->alert();
                }

                if ($form->task_target == TaskTargetType::ContinuousDay) {
                    $target_condition = (float)$form->target_condition;
                    if ($target_condition <= 0) return $form->response()->error('请设置连续N天的目标条件')->alert();
                }

                if ($form->task_target == TaskTargetType::Every) {
                    $repetition = (int)$form->repetition;

                    if ($repetition <= 0) return $form->response()->error('选择每次时，必须开启重复完成选项')->alert();
                }

                if ($user_award_rate > 0 && !in_array($form->hook, [UserHookType::Recharge, UserHookType::Earnings])) return $form->response()->error('奖励比例只支持充值、收益事件')->alert();

                if ($form->task_target == TaskTargetType::First && $form->isCreating()) {
                    if (Task::query()->where('task_target', TaskTargetType::First)->where('hook', $form->hook)->exists()) return $form->response()->error('无法创建多个首次目标事件')->alert();
                }

                if ($form->wallet_type == WalletType::balance) {
                    if ($is_deduct) return $form->response()->error('奖励类型为余额无法提现扣除')->alert();
                    //if ($form->hook !== UserHookType::Invite) return $form->response()->error('余额奖励只支持邀请事件')->alert();
                }

            });


        });
    }
}
