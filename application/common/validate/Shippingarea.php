<?php
namespace app\common\validate;

use think\Validate;
class ShippingArea extends Validate {

    protected $rule = [

      ['transport_id', 'require', '请输入运费模版id'],
      ['shipping_area_name', 'require', '请输入运费模版区域名称'],
      ['config', 'require', '请输入运费模版区域配置']
    ];
}
