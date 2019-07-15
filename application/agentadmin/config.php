<?php

// $admin_config = [];

// $html_config = include_once 'html.conf.php';
// return array_merge($admin_config,$html_config);
//
return [
  'view_replace_str'  =>  [
      '__PUBLIC__'=>'/public',
      '__AGENT_ADMIN_STATIC__' => '/public/static/agentadmin/',
  ],
  'dispatch_success_tmpl'  => 'public/dispatch_jump.ts',
  'dispatch_error_tmpl'    => 'public/dispatch_jump.ts',
];
