<?php
namespace app\common\validate;

use think\Validate;
class Shopcar extends Validate {

    protected $rule = [
      ['user_id', 'require', '还没有登录'],
      ['goods_id', 'require', '请选择商品'],
      ['spec_config', 'require', '请选择商品规格'],
      ['number', 'number|gt:0', '请选择数量']
    ];
}
