<?php

// $admin_config = [];

// $html_config = include_once 'html.conf.php';
// return array_merge($admin_config,$html_config);
//
return [
 	'template'=>[
	 	// 模板路径
    'view_path'    => WEB_ROOT.'/thems/default/',
    // 模板布局开关
    'layout_on'    => false,
    // 模板布局文件
    'layout_name'  => 'layout',
	],
  'view_replace_str'  =>  [
      '__PUBLIC__'=>'/public',
      '__HOME_STATIC__' => '/thems/default/static',
      '__ROOT__'=>''
  ],
  'dispatch_success_tmpl'  =>'public/dispatch_jump.tpl',
  'dispatch_error_tmpl'    => 'public/dispatch_jump.tpl',
];
