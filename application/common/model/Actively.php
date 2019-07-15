<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19 0019
 * Time: 下午 5:29
 */

namespace app\common\model;


use think\Model;

class Actively extends Model {
  protected $createTime = 'createtime';
  protected $updateTime = 'updatetime';
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
}
