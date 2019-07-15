<?php
/**
 * fumanx 模块路由
 */
use think\Route;
Route::group('fumanx', function () {
	Route::get('/index/$', 'fumanx/index/index');
	Route::get('/index/home/$', 'fumanx/index/home');

	//用户
	Route::get('/user/index/', 'fumanx/User/index');
	Route::get('/address/index/', 'fumanx/Address/index');
});
