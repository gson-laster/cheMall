<?php

namespace app\common\model;

use think\Model;

class GoodsModel extends Model
{
  protected $insert = ['createtime'];
  protected $update = ['updatetime'];
  protected function initialize(){
    parent::initialize();
  }
  /**
   * 创建时间
   * @return bool|string
   */
  protected function setCreatetimeAttr($value) {
      return time();
  }
  /**
   * [getCreatetimeAttr 获取创建时间]
   * @param    [type]                   $value [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T12:27:10+080
   */
  public function getCreatetimeAttr($value)
  {
        return date("Y-m-d H:i:s", $value);
  }
  /**
   * [setUpdatetimeAttr 更新时间]
   * @Author:  slade
   * @DateTime :2017-03-23T10:35:28+080
   */
  protected function setUpdatetimeAttr() {
      return time();
  }
}
