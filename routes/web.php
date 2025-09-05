<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 通用登录路由重定向到管理员登录
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');
