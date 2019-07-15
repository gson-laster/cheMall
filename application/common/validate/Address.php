<?php
namespace app\common\validate;

use think\Validate;
class Address extends Validate {
    // protected $rule = [
    //     ['name'       => 'require|length:4,32'],
    //     'phone'        => 'require|unique',
    //     'province'     => 'require',
    //     'city'         => 'require',
    //     'area'         => 'require',
    //     'uid'           => 'require'
    // ];
    //
    // protected $message = [
    //     'name.require'         => '请输入收货人姓名',
    //     'name.length'            => '姓名输入不合法',
    //     'phone.require'          => '请输入收货人电话',
    //     'phone.unique'          => '电话号码已存在',
    //     'province'            => '请选择省份',
    //     'city'                => '请选择市',
    //     'area'               => '请选择区域',
    //     'uid'                => '请先登录'
    // ];

    protected $rule = [

      ['name', 'require', '请输入收货人姓名'],
      ['name', 'length:4,32', '姓名输入不合法'],
      ['phone', 'require', '请输入收货人电话'],
      ['province', 'require', '请选择省份'],
      ['city', 'require', '请选择市'],
      ['area', 'require', '请选择区域'],
      ['uid', 'require', '请先登录'],
    ];
}
