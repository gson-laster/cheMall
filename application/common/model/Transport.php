<?php

namespace app\common\model;

use think\Model;

class Transport extends Model
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
          return date("Y-m-d H:i:s");
    }
    /**
     * [setStatusAttr 设置状态]
     * @param    [type]                   $value [description]
     * @Author:  slade
     * @DateTime :2017-03-23T14:51:13+080
     */
    public function setStatusAttr($value){
      $status = ['正常'=>1, '禁用'=> 0];
      // dump($status[$value]);exit;
      return $status[$value];
    }
    /**
     * [getStatusAttr 状态]
     * @param    [type]                   $value [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-23T10:57:21+080
     */
    public function getStatusAttr($value)
    {
          $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
          return $status[$value];
    }


}
