<?php

namespace app\common\model;

use think\Model;

class Admin extends Model
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
   * [getCreatetimeAttr 获取创建时间]
   * @param    [type]                   $value [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T12:27:10+080
   */
  public function getUpdatetimeAttr($value)
  {
        return date("Y-m-d H:i:s", $value);
  }
}
