<?php

namespace App\Admin\Actions\Grid;

use App\Models\LanguageConfig;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CloneLanguageConfig extends RowAction
{

    protected $title = '复制';

    public function handle(Request $request)
    {
        $slug = $this->getKey();

        $item = LanguageConfig::query()->where('slug', $slug)->first();


        $newItem = $item->replicate();

        $newItem->slug = $slug . '_COPY';

        $newItem->save();

        return $this->response()
            ->success('复制成功')
            ->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定要复制?'];
    }

}
