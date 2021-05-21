<?php

namespace App\Admin\Actions;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Modal;
class Clear extends Action
{
    protected $title = '清理缓存';

    public function render()
    {
        $cache = \App\Admin\Actions\Form\CacheClear::make();
        $config = \App\Admin\Actions\Form\ConfigClear::make();
        $route = \App\Admin\Actions\Form\RouteClear::make();
        $opcache = \App\Admin\Actions\Form\OpCacheClear::make();
        $modalCache = \App\Admin\Actions\Form\ModalCacheClear::make();
        $modal = Modal::make()
            ->id('clear')
            ->title($this->title())
            ->body(
                <<<HTML
                $cache $config $route $opcache $modalCache
                HTML
            )
            ->button(
                <<<HTML
                    <a class='mr-1' href="javascript:void(0);">清理</a>
                HTML
            );

        return $modal->render();
    }
}
