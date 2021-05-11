<?php

namespace App\Admin\Actions\Grid;

use App\Models\User;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetUserJwtToken extends RowAction
{

    protected $title = '获取登录TOKEN';



    public function handle(Request $request)
    {
        $id = $this->getKey();

        $user = User::query()->find($id);

        $token = auth('api')->login($user);

        return $this->response()
            ->success("<div class='fs-12'><textarea rows='8' style='width:400px;'>$token</textarea></div>")
            ->alert();
    }


}
