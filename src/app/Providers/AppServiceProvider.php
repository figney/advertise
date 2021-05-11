<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }


    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {


            $setting = Setting::query()->where('channel_id', 1)->firstOrFail();

            collect($setting)->each(function ($value, $key) {
                config(['setting.' . $key => $value]);
            });
        } catch (\Exception $exception) {

        }

    }
}
