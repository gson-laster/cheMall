<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/29 0029
 * Time: 下午 3:13
 */

namespace app\common\model;


use think\Model;

class ServiceApply extends  Model
{

    protected $insert = ['createtime'];
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

}