<?php
namespace app\home\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Config;
use app\common\model\User;
use app\common\model\Order as OrderModel;


/**
 * 余额支付
 */
class Remainder extends Controller {
  protected $user;

  protected function _initialize () {
    parent::_initialize();
    $this->user = new User();
  }

  public function toPay ( $orderId, $backurl = '' ) {
    if ( !$orderId ) {
      $this->error( '缺少必要参数' );
    }
    if ( !Session::get( 'qt_userId' ) ) {
      $this->error( '没有登录，请先登录', url( "home/login/index" ) );
    }
    $orderMdl = new OrderModel();
    $order_res = $orderMdl->where( "pay_status=1" )->find( $orderId );
    if ( !$order_res ) {
      $this->error( '没有这样的订单', url( "home/order/topay", [ "paycode" => $this->request->controller(), 'orderid' => $orderId, 'res' => 'fail' ] ) );
    }

    $user_vb = $this->user->field( [ 'user_vb', 'id' ] )->find( Session::get( 'qt_userId' ) );

    $order_amount = $order_res->total_amount;

    if ( $order_amount * 1 > $user_vb[ 'user_vb' ] * 1 ) {
      $this->error( "余额不足，请先充值或者改换支付方式", url( "home/order/detail", [ "order_id" => $orderId ] ) );
    }

    $flag = (new \app\common\model\Order())->order_handle($order_res, $order_res['order_sn'], function ($result) {
      L('支付结果'.var_export($result, true));
      if($result['code'] == 'success') {
        accountLog($result['order_res']['user_id'], $result['order_res']['total_amount'], 2, '购买商品支付订单'.$result['order_res']['order_sn']);
        $this->success('支付成功', $result['backUrl']);
      }else{
        $this->error('支付失败', $result['backUrl']);
      }
    });
  }
}/*
orderPay($order_res, 'Remainder', function ($order_res) {

  accountLog($order_res['user_id'], $order_res['total_amount'], 2, '购买商品支出');
}, function ($result) {
  L('支付结果'.var_export($result, true));
  if($result['code'] == 'success') {
    $this->success('支付成功', $result['backUrl']);
  }else{
    $this->error('支付失败', $result['backUrl']);
  }
});*/
