<?php

namespace app\common\model;

use think\Model;

class Region extends Model
{
    protected function initialize(){
      parent::initialize();
    }
    public function getByIdIn($str){
      $map['id'] = array('exp', "IN (".$str.") ");
      $res = $this->where($map)->select();
      if(strstr('-1', $str)){
        $arr = [
          'name' => '全国',
        ];
        array_push($res,);
      }
      return
    }
}
