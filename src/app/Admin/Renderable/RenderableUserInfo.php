<?php


namespace App\Admin\Renderable;


use App\Models\User;
use Dcat\Admin\Support\LazyRenderable;

class RenderableUserInfo extends LazyRenderable
{

    public function render()
    {
        $user_id = $this->payload['user_id'];

        $user = User::query()->find($user_id);

        return view('admin.user-info-modal', ['user' => $user]);
    }
}
