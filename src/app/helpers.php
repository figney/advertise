<?php

use App\Services\AppService;
use App\Services\LangService;
use Carbon\Carbon;

function Lang($slug, array $params = [], $local = null)
{


    if (empty($slug)) {
        return null;
    }


    //获取当前语言
    $local = AppService::make()->local($local);


    $langContent = LangService::make()->getLang($local, $slug);


    if (empty($langContent)) {
        LangService::make()->createLang($slug, $params);
    }

    $langContent = $langContent ?? $slug;

    if (count($params) > 0) {
        foreach ($params as $key => $value) {
            $langContent = str_replace("{" . $key . "}", $value, $langContent);
        }

    }

    return $langContent ?? $slug;

}

function Setting(string $slug, $default = null)
{

    return config('setting.' . $slug, $default);

}

function SettingBool(string $slug, $default = null)
{
    return (bool)config('setting.' . $slug, $default);
}

function SettingLocal(string $slug, $default = null, $local = null)
{

    $l = AppService::make()->local($local);

    return data_get(config('setting.' . $slug, $default), $l);
}

function LocalDataGet($data, $local = null)
{
    $local = AppService::make()->local($local);
    return data_get($data, $local);
}

function ImageUrl($path, $disk = null)
{
    if (empty($path)) return $path;

    if (Str::contains($path, '//')) {
        return $path;
    }

    return Storage::disk($disk)->url($path);
}

function TimeFormat($time)
{
    return $time ? Carbon::make($time)->format("Y-m-d H:i:s") : null;
}

function UsdtToBalance($v): float
{
    return $v * Setting('usdt_money_rate');
}

function FbToRmb($v)
{
    $vv = $v / Setting('rmb_money_rate');

    return number_format($vv, $vv > 100 ? 0 : 2);
}

function MoneyFormat($money)
{
    return round($money, Setting('money_decimal'));
}

function ShowMoney($value, $isUsdt = false)
{
    $f_value = (float)$value;

    if ($f_value === 0) return "-";

    if ($isUsdt) $f_value = UsdtToBalance($value);

    $fiat_code = Setting('fiat_code');

    //$money = number2chinese(round($f_value));


    $html = "";
    $html .= $isUsdt ? "<div class='text-bold'>" . (float)$value . " U</div>" : "";
    $html .= "<div class='text-bold'>" . round($f_value, 4) . "<span class='margin-left-xs'>$fiat_code</span></div>";
    //$html .= $f_value >= 1000 ? "<div> $money </div>" : "";
    $html .= $f_value > 0 ? "<div>≈ " . FbToRmb($f_value) . "元</div>" : "";
    return $html;
}

function ShowRmb($value, $isUsdt = false)
{
    $f_value = (float)$value;

    if ($f_value === 0) return "-";

    if ($isUsdt) $f_value = UsdtToBalance($value);

    return $f_value > 0 ? "≈ " . FbToRmb($f_value) . "元" : "";


}

function ShowMoneyLine($value, $isUsdt = false)
{
    $f_value = (float)$value;

    if ($f_value === 0) return "-";

    if ($isUsdt) $f_value = UsdtToBalance($value);

    return $f_value !== 0 ? $f_value . Setting('fiat_code') . "<span class='margin-left-xs text-80'>≈ " . FbToRmb($f_value) . "元</span>" : "0";
}

function MoneyShow($v)
{
    return (float)$v;
}
