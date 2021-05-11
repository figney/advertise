<?php

namespace App\Admin\Actions\Grid;

use App\Models\User;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SetUserPassword extends RowAction
{

    protected $title = '重置密码';


    public function handle(Request $request)
    {
        $id = $this->getKey();

        $user = User::query()->find($id);

        $user->password = \Hash::make("123456");

        $user->save();

        return $this->response()
            ->success("密码已经重置为：123456")
            ->alert();
    }

    public function confirm()
    {
        return ['是否重置密码'];
    }


}
