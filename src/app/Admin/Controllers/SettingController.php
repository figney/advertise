<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Setting;
use App\Enums\CountryCode;
use App\Models\Language;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class SettingController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Setting(), function (Grid $grid) {
            $grid->model()->with('channel');
            $grid->column('channel.name', '渠道');
            $grid->column('default_lang', '默认语言');
            $grid->column('default_currency');

            $grid->filter(function (Grid\Filter $filter) {

                $filter->equal('channel_id');
            });
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->setActionClass(Grid\Displayers\Actions::class);
        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $language = Language::query()->get();
        return Form::make(new Setting(), function (Form $form) use ($language) {

            $form->defaultEditingChecked();

            $form->tab("基础", function (Form $form) {

                $form->radio('default_lang', '默认语言')->options(Language::query()->where('status', true)->get()->pluck('name', 'slug'))->help('default_lang：当无法获取语言环境时将会使用此值');
                $form->text('time_format', 'JS日期格式')->help('time_format');
                $form->number('app_version_code', 'APP版本标识')->help('app_version_code');
                $form->url('app_download_url', 'APP下载地址')->help('app_download_url');
                $form->url('socket_url', '消息推送地址')->help('socket_url');
                $form->url('web_url', '前端地址')->help('web_url');

                $form->textarea('web_js_code', '前端JS代码')->help('web_js_code');


            })->tab("用户", function (Form $form) {

                $form->number('device_reg_max', '设备注册上限')->help('device_reg_max:同一个设备最多可注册用户');

                $form->list('country_code', '注册开启国家码')->width(3)->help('country_code：<a target="_blank" href="http://www.loglogo.com/front/countryCode/">查看国家码列表</a>');

                $form->switch('is_sms_reg', '使用短信注册')->help('is_sms_reg');

                $form->radio('open_recaptcha', '是否开启谷歌验证')->help('open_recaptcha')
                    ->options([1 => '开启', 0 => '关闭'])
                    ->when(1, function (Form $form) {
                        $form->text('google_web_recaptcha', '谷歌验证码客户端KEY')->help('google_web_recaptcha');
                        $form->text('google_serve_recaptcha', '谷歌验证码服务端KEY')->help('google_serve_recaptcha');
                        $form->list('google_check_domains', '谷歌验证域名白名单')->help('google_check_domains')->width(2);
                    });


            })->tab("钱包", function (Form $form) {

                $form->text('default_currency', '货币单位')->required()->help('default_currency：用于客户端展示用')->width(2);
                $form->text('fiat_code', '国际货币单位')->required()->help('fiat_code：用于获取实时汇率')->width(2);
                $form->switch('show_suffix', '前端换算K单位金额')->help('show_suffix');
                $form->number('money_decimal', '余额显示小数点')->help('money_decimal');
                $form->number('usdt_decimal', 'USDT显示小数点')->help('usdt_decimal');
                $form->switch('open_transform', '开启余额转换')->help('open_transform');
                $form->text('usdt_money_rate', 'USDT- ' . Setting("default_currency") . ' 汇率')->width(2)->required()->help('usdt_money_rate：1USDT=多少' . Setting("default_currency") . '，系统每分钟更新汇率');
                $form->text('rmb_money_rate', '人民币- ' . Setting("default_currency") . ' 汇率')->width(2)->required()->help('rmb_money_rate：1元=多少' . Setting("default_currency"));
                $form->number("usdt_to_money_min", "USDT转余额最小值")->required()->help('usdt_to_money_min');
                $form->number("money_to_usdt_min", "余额转USDT最小值")->required()->help('money_to_usdt_min');

            })->tab("充值", function (Form $form) use ($language) {

                $form->radio('open_recharge', '开启充值功能')->options([1 => '开启', 0 => '关闭'])->help('open_recharge:关闭后用户将无法充值')->when(0, function (Form $form) use ($language) {
                    $form->embeds('close_recharge_describe', '关闭充值说明', function (Form\EmbeddedForm $form) use ($language) {
                        foreach ($language as $lang) {
                            $lang->show ? $form->text($lang->slug, $lang->name)->help('close_recharge_describe') : $form->hidden($lang->slug, $lang->name);
                        }
                    });
                });
                $form->list('first_recharge_select', '第一次充值选择金额')->help('first_recharge_select')->width(2);
                $form->list('recharge_select', '充值选择金额')->help('recharge_select')->width(2);

            })->tab("提现", function (Form $form) use ($language) {

                $form->radio('open_withdraw', '开启提现功能')->options([1 => '开启', 0 => '关闭'])->help('open_withdraw:关闭后用户将无法充值')->when(0, function (Form $form) use ($language) {
                    $form->embeds('close_withdraw_describe', '关闭充值说明', function (Form\EmbeddedForm $form) use ($language) {
                        foreach ($language as $lang) {
                            $lang->show ? $form->text($lang->slug, $lang->name)->help('close_withdraw_describe') : $form->hidden($lang->slug, $lang->name);
                        }
                    });
                });

                $form->number('min_withdraw', '最低提现金额')->help('min_withdraw');


            })->tab("赚钱宝", function (Form $form) {

                $form->switch('mb_open_in', '允许转入')->help('mb_open_in');
                $form->switch('mb_open_out', '允许转出')->help('mb_open_out');

                $form->rate('mb_balance_rate', '余额年化率')->help('mb_balance_rate')->width(2)->type('number')->rules('required|numeric|min:0|max:100');
                $form->rate('mb_usdt_rate', 'USDT年化率')->help('mb_usdt_rate')->width(2)->type('number')->rules('required|numeric|min:0|max:100');
                $form->rate('mb_give_rate', '赠送金年化率')->help('mb_give_rate')->width(2)->type('number')->rules('required|numeric|min:0|max:100');

            })->tab('任务', function (Form $form) {
                $form->number('free_task_num', '免费任务次数')->help('free_task_num');
            });


            $form->disableDeleteButton();
            $form->disableCreatingCheck();
            $form->disableViewButton();
            $form->disableResetButton();

        });
    }
}
