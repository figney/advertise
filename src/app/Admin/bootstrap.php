<?php

use App\Admin\Renderable\RenderableUserInfo;
use App\Admin\Renderable\UserWalletLogTable;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Column;
use Dcat\Admin\Grid\Filter;
use Dcat\Admin\Layout\Navbar;
use Dcat\Admin\Widgets\Modal;

/**
 * Dcat-admin - admin builder based on Laravel.
 * @author jqh <https://github.com/jqhph>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 *
 * extend custom field:
 * Dcat\Admin\Form::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Column::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Filter::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */
//Admin::js();


Admin::css([
    "https://unpkg.com/element-ui/lib/theme-chalk/index.css",
    "css/admin.css"
]);

Admin::js([
    "https://unpkg.com/vue/dist/vue.js",
    "https://unpkg.com/element-ui/lib/index.js",
    "https://unpkg.com/axios/dist/axios.min.js",
    //"https://cdnjs.cloudflare.com/ajax/libs/socket.io/3.1.2/socket.io.js"
    //"https://unpkg.com/@antv/g2plot@latest/dist/g2plot.min.js"
]);

Grid::resolving(function (Grid $grid) {
    $grid->paginate(10);
    $grid->addTableClass('fs-12');
    $grid->disableRowSelector();
    $grid->disableViewButton();
    $grid->setActionClass(Grid\Displayers\ContextMenuActions::class);
    $grid->filter(function (Filter $filter) {
        $filter->panel();
    });
});

Form::resolving(function (Form $form) {
    //$form->disableEditingCheck();

    $form->disableCreatingCheck();

    $form->disableViewCheck();

    $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
    });

});

Column::extend('userInfo', function (?\App\Models\User $user) {

    if (!$user) {
        return "-";
    }

    $model = Modal::make()
        ->xl()
        ->title('用户详情')
        ->body(RenderableUserInfo::make()->payload(['user_id' => $user->id]))
        ->button('<button class="btn btn-outline-primary sm-btn">用户详情</button>');

    $logModel = Admin::user()->inRoles(['administrator', 'channel', 'son_channel']) ? Modal::make()
        ->xl()
        ->title('用户账号明细')
        ->body(UserWalletLogTable::make()->payload(['user_id' => $user->id]))
        ->button('<button class="btn btn-outline-primary sm-btn">钱包流水</button>') : "";

    return view('admin.user-info-grid', ['user' => $user, 'model' => $model, 'logModel' => $logModel]);
});

Column::extend('money', function ($value, $isUsdt = false) {


    return ShowMoney($value, $isUsdt);
});
Column::macro('minWidth', function ($width) {

    return $this->setAttributes(['style'=>'width:'.$width.'px']);
});
Column::extend('localData', function ($value, $local = null, $limit = 100) {

    return \Illuminate\Support\Str::limit(LocalDataGet($value, $local), $limit);
});


Admin::navbar(function (Navbar $navbar) {

    if (Admin::user() && Admin::user()->isAdministrator()) {
        $navbar->right("<a class='mr-1' href=" . admin_url('settings/1/edit') . " >设置</a>");
        $navbar->right("<a class='mr-1' target='_blank' href=" . admin_url('logs') . " >日志</a>");
        $navbar->right("<a class='mr-1' target='_blank' href=" . url('horizon') . " >队列</a>");
        if (App::isLocal()) $navbar->right("<a class='mr-1' target='_blank' href=" . url('telescope') . " >调试</a>");
        //$navbar->right(view('admin.right-navbar'));
        $navbar->left(view('admin.left-navbar'));
    }


});
