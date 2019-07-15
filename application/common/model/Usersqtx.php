<?php
namespace app\common\model;

use think\Model;

class Usersqtx extends Model
{

    protected $insert = ['createtime'];
    protected $update = ['isoktime'];

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
    protected function setIsoktimeAttr() {
        return time();
    }
}

?>