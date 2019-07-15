<?php
namespace app\common\validate;

use think\Validate;
class User extends Validate {
    protected $rule = [
        'phone'            => 'require|unique:user|number',
        'password'         => 'require',
    ];

    protected $message = [
        'phone.require'         => '请输入电话号码',
        'password.require'         => '请输入密码',
        'phone.unique'          => '电话号码已存在',
        'phone.number'            => '电话号码格式错误',
    ];
}
