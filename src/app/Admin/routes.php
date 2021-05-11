<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'domain' => config('admin.route.domain'),
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->resource('/settings', 'SettingController');
    $router->resource('/sms', 'SmsController');
    $router->resource('/language', 'LanguageController');
    $router->resource('/language_config', 'LanguageConfigController');


    $router->resource('/channels', 'ChannelController');
    $router->resource('/channel_service', 'ChannelServiceController');
    $router->resource('/channel_link', 'ChannelLinkController');


    $router->resource('/user_list', 'UserController');
    $router->resource('/user_invites', 'UserInviteController')->only(['index']);
    $router->resource('/wallet_logs', 'WalletLogController');
    $router->resource('/shares', 'ShareController');
    $router->resource('/domains', 'DomainController');
    $router->resource('/articles', 'ArticleController');
    $router->resource('/devices', 'DeviceController')->only(['index']);
    $router->get('/device_statistics', 'DeviceController@statistics');
    $router->resource('/device_logs', 'DeviceLogController')->only(['index']);
    $router->get('/device_log_statistics', 'DeviceLogController@statistics');
    $router->resource('/banners', 'BannerController');
    $router->resource('/products', 'ProductController');
    $router->resource('/tasks', 'TaskController');
    $router->resource('/money_bao', 'MoneyBaoController');
    $router->resource('/user_products', 'UserProductController');
    $router->resource('/recharge_channels', 'RechargeChannelController');
    $router->resource('/recharge_channel_list', 'RechargeChannelListController');
    $router->resource('/user_transfer_voucher', 'UserTransferVoucherController');
    $router->resource('/withdraw_channel', 'WithdrawChannelController');
    $router->resource('/withdraw_channel_list', 'WithdrawChannelListController');
    $router->resource('/user_recharge_orders', 'UserRechargeOrderController');
    $router->resource('/user_withdraw_orders', 'UserWithdrawOrderController');
    $router->resource('/usdt_address', 'RechargeCoinAddressController');

    $router->resource('/vips','VipController');
    $router->resource('/ad_tasks','AdTaskController');
    $router->resource('user_vips','UserVipController');
    $router->resource('user_ad_tasks','UserAdTaskController');


    //数据中心
    $router->get('/walletLogCount', 'DataCountController@walletLogCount');

    $router->get('/getOnlineNum','ServeController@getOnlineNum')->name('admin.getOnlineNum');
    $router->get('/getSignData','ServeController@getSignData')->name('admin.getSignData');
    $router->get('/wsReload','ServeController@wsReload');

});
