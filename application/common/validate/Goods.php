<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/31 0031
 * Time: 下午 4:04
 */

namespace app\common\validate;
use think\Validate;

class Goods extends Validate
{
	// 验证规则
	protected $rule = [
		['goods_name','require','商品名称必填'],
		['cat_id', 'number|gt:0', '商品分类必须填写|商品分类必须选择'],
		['artnum', 'unique:goods', '商品货号重复'], // 更多 内置规则 http://www.kancloud.cn/manual/thinkphp5/129356
		['shop_price','regex:\d{1,10}(\.\d{1,2})?$','本店售价格式不对。'],
		['market_price','regex:\d{1,10}(\.\d{1,2})?$','市场价格式不对。'],
		['weight','regex:\d{1,10}(\.\d{1,2})?$','重量格式不对。'],
	];
}
