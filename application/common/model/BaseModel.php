<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 下午 4:41
 */

namespace app\common\model;


use think\Model;

class BaseModel extends Model {
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
}
