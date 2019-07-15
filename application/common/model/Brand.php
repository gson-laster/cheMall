<?php

namespace app\common\model;

use think\Model;

class Brand extends Model
{
    protected function getIsHotAttr($values){
      $hots = [1=>'是', 0=>'否'];
      return $hots[$values];
    }
}
