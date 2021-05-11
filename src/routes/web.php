<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShareController;

Route::get("/", ShareController::class . "@share");
Route::get("/at", ShareController::class . "@share");
Route::get("/xx-zw", HomeController::class . "@index");



