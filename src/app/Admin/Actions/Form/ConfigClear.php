<?php

namespace App\Admin\Actions\Form;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Form\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Runner\Exception;

class ConfigClear extends AbstractTool
{
    /**
     * @return string
     */
	protected $title = '配置';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {

        try {
            Artisan::call('config:clear');
            return $this->response()
                ->success('清理成功');
        } catch (Exception $e) {
            return $this->response()
                ->success('清理失败');
        }
    }

    /**
     * @return string|void
     */
    protected function href()
    {
        // return admin_url('auth/users');
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		 return ['确定清理配置吗？', '清除配置文件缓存！！'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
