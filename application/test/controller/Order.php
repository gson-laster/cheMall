<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5 0005
 * Time: 下午 3:30
 */

namespace app\test\controller;


use think\Controller;

class Order extends Controller {
  public function backzk($orderId){
    if(!$orderId) return '';
    $res = (new \app\common\model\Order())->payendBackZk($orderId);
    if($res){
      echo 'success';
    }else{
      echo 'fail';
    }
  }
}
