<?php
namespace app\common\validate;

use think\Validate;
class Admin extends Validate {

    protected $rule = [

      ['user_name', 'require', '请输入管理员用户名'],
      ['user_name', 'unique:admin', '已经存在管理员'],
      ['user_name', 'length:4,32', '姓名输入不合法'],
      ['password', 'require', '请输入管理员密码'],
      ['password_confirm', 'confirm:password', '请确认管理员密码'],
      ['password', 'length:6,32', '密码输入不合法']
    ];
}
