<?php

namespace app\common\model;

use think\Model;
use think\model\Merge;

class Spec extends Merge
{
  // protected $insert = ['createtime'];
  // protected $update = ['updatetime'];
  //
  // // 设置主表名
  // protected $table = 'ty_spec';
  // // 定义关联模型列表
  // protected $relationModel = [
  //     // 给关联模型设置数据表
  //     'spec_item'   =>  'ty_spec_item',
  // ];
  // // 定义关联外键
  // protected $fk = 'type_id';
  // protected $mapFields = [
  //     // 为混淆字段定义映射
  //     'name' =>  'spec_item.name',
  // ];

  protected function initialize(){
    parent::initialize();
  }

  /**
    * 后置操作方法
    * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
    * @param int $id 规格id
    */
   public function afterSave($id, $items)
   {

       $model = new SpecItem(); // 实例化User对象
       $post_items = explode(',', $items);
      //  dump(PHP_EOL);
      //  dump($post_items);exit;
       foreach ($post_items as $key => $val)  // 去除空格
       {
           $val = str_replace('_', '', $val); // 替换特殊字符
           $val = str_replace('@', '', $val); // 替换特殊字符

           $val = trim($val);
           if(empty($val))
               unset($post_items[$key]);
           else
               $post_items[$key] = $val;
       }
       $db_items = $model->where("spec_id = $id")->column('id,item');
       // 两边 比较两次
       $dataList=[];
       /* 提交过来的 跟数据库中比较 不存在 插入*/
       foreach($post_items as $key => $val)
       {
           if(!in_array($val, $db_items))
               $dataList[] = array('spec_id'=>$id,'item'=>$val);
       }
       // 批量添加数据
       !empty($dataList) && $model->insertAll($dataList);

      //  /* 数据库中的 跟提交过来的比较 不存在删除*/
      //  foreach($db_items as $key => $val)
      //  {
      //      if(!in_array($val, $post_items))
      //      {
      //          //  SELECT * FROM `tp_spec_goods_price` WHERE `key` REGEXP '^11_' OR `key` REGEXP '_13_' OR `key` REGEXP '_21$'
      //          $price = new SpecGoodsPrice();
      //          $price->where("`key` REGEXP '^{$key}_' OR `key` REGEXP '_{$key}_' OR `key` REGEXP '_{$key}$' or `key` = '{$key}'")->delete(); // 删除规格项价格表
      //          $model->where('id='.$key)->delete(); // 删除规格项
      //      }
      //  }
   }



}
