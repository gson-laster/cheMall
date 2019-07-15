<?php

namespace app\common\model;

use think\Model;
use think\Config;

class Goods extends Model
{
  protected $createTime = 'createtime';
  protected $updateTime = 'updatetime';
  protected $insert = ['createtime'];
  protected $update = ['updatetime'];

  //自定义初始化
  protected function initialize()
  {
      //需要调用`Model`的`initialize`方法
      parent::initialize();
  }

  /**
   * 创建时间
   * @return bool|string
   */
  protected function setCreatetimeAttr() {
      return time();
  }

  /**
   * [setUpdatetimeAttr 更新时间]
   * @Author:  slade
   * @DateTime :2017-03-23T10:35:28+080
   */
  protected function setUpdatetimeAttr() {
      return time();
  }

  public function getCreatetimeAttr($value){
    return date("Y-m-d", $value);
  }
  //shipping_area_ids
  protected function getShippingAreaIdsAttr($value){
    return json_decode($value, true);
  }
  //支付方式
  protected function getPaymentAttr($value){
    return explode(',', $value);
  }
  /**
   * 获取商品列表
   * @return   {[object]                  [商品对象]
   * @Author:  slade
   * @DateTime :2017-04-05T15:25:57+080
   */
  public function getGoodsList($where, $page){
    $res = $this->alias('g')->field(['g.id','g.title','g.artnum','g.cat_id','g.shop_price','g.is_recommend','g.is_new','g.is_hot','g.is_on_sale','g.createtime','g.store_count','g.sort','cate.name as cate_name'])->join("__GOODS_CATEGORY__ cate"," g.cat_id=cate.id")->where($where)->paginate(Config::get('pageSize'), false, ['page'=>$page]);
    return $res;
  }

  /**
	 * [根据分类获取商品]
	 * @param    [type]                   $cate_id [description]
	 * @return   {[type]                  [description]
	 * @Author:  slade
	 * @DateTime :2017-04-06T15:27:21+080
	 */
	public function getGoodsByCate($cate_id, $where = [], $pageSize, $page,$field=[]){
		$goods_data = array();
		$goods = new Goods();
		$where['is_on_sale'] = 1;
		// dump(is_null(cate_is_first($cate_id)));exit;
		if(!is_null(cate_is_first($cate_id))){
      // dump(cate_is_first($cate_id));exit;
			if(cate_is_first($cate_id)){
				//是顶级分类呢
				if(cate_has_child($cate_id)){
						//有下级
						$child = return_child_cate($cate_id);
						array_push($child, $cate_id);
						$ids = implode(',',$child);
						$where['cat_id'] = ["exp", "in($ids)"];
						$goods_data = $goods->field($field)->where($where)->order('id DESC')->paginate($pageSize, false, ['page'=>$page]);
					}else{
						//没有下级
						$where['cat_id'] = $cate_id;
						$goods_data = $goods->field($field)->where($where)->order('id DESC')->paginate($pageSize, false, ['page'=>$page]);
					}
				}else{
					//不是顶级分类
					// dump(cate_has_child($cate_id));exit;
					if(cate_has_child($cate_id)){
						//有下级
						$child = return_child_cate($cate_id);
            // dump($child);
						array_push($child, $cate_id);
						$ids = implode(',',$child);
						$where['cat_id'] = ["exp", "in($ids)"];
						$goods_data = $goods->field($field)->where($where)->order('id DESC')->paginate($pageSize, false, ['page'=>$page]);
						// dump($goods->field($field)->getLastSql());exit;
					}else{
						// 没有下级
						$where['cat_id'] = $cate_id;
						$goods_data = $goods->field($field)->where($where)->order('id DESC')->paginate($pageSize, false, ['page'=>$page]);
            // dump($goods->getLastSql());
					}
				}
			}
		return $goods_data;
	}

  /**
   * 自定义保存之后的操作
   * @param    [type]                   $id           [description]
   * @param    [type]                   $images_data  [description]
   * @param    [type]                   $detaile_data [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-04-07T11:43:31+080
   */
  public function afterSave($id, $images_data, $detaile_data){

  }
}
