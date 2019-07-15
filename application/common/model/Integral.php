<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 2:47
 */

namespace app\common\model;


use think\Model;

class Integral extends  Model
{

    protected $insert = ['updatetime'];


    /**
     * 创建时间
     * @return bool|string
     */
    protected function setUpdatetimeAttr() {
        return time();
    }



}