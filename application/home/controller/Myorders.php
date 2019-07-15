<?php
namespace app\home\controller;

use think\Db;
use think\Session;
use think\Config;
use app\api\controller\Shipping;
use app\common\model\Order;

class Myorders extends HomeBase {

  function getorder ( $status = '', $pay_status = "", $page = 1, $pagesize = 10 ) {

    if ( !$status ) {
      $this->error( '缺少必要参数' );
    }

    $userids = Session::get( 'qt_userId' );
    $order   = new Order();
    $where   = "d.user_id=" . $userids . Config::get( strtoupper( $status ) );

    $list = $order->alias( 'd' )->where( $where )->order( "order_id desc" )->paginate( $pagesize, false, [ 'page' => $page ] );
     //dump($list);exit;
    $order_res   = convert_arr_key( $list, 'order_id' );
    $update_data = [];
    foreach ( $list as $k => $v ) {
      if ( $v[ 'shipping_time' ] + cache_data( 'site_config' )[ 'getGoods' ] >= time() ) {
        $update_data = [ 'order_id' => $v[ 'order_id' ], 'order_status' => 3, 'confirm_time' => time() ];
        $order->where( $where . " AND order_status=2 AND shipping_status=2 AND shipping_time" )->update( $update_data );
      }
    }
    // dump($order);
    //自动收货
    // $order->where($where." AND order_status=2 AND shipping_status=2 AND shipping_time")

    $order_id                  = array_keys( $order_res );
    $goods_where[ 'order_id' ] = [ 'exp', "in (" . implode( ',', $order_id ) . ")" ];
    $order_goods               = [];
    // dump(count($list));
    if ( count( $list ) ) {
      $order_goods = Db::name( 'order_goods' )->alias( 'og' )->field( [ 'og.*' ] )->where( $goods_where )->select();
    }
    // dump($order->getLastSql());
    ///dump($list);
    foreach ( $order_goods as $key => &$value ) {
      $value[ 'thumb' ] = goods_thum_images( $value[ 'goods_id' ], 200, 200 );
    }
    if ( $this->request->isAjax() ) {
      if ( $list && $order_goods ) {

        return json( [ 'code' => 1002, "data" => [ 'list' => $list, 'goods_list' => $order_goods ] ] );
      } else {
        return json( [ 'code' => 1001, "data" => [] ] );
      }
    } else {


          $this->assign( "list", $list );
          $this->assign( 'goods', $order_goods );
      return $this->fetch( 'order/orderList' );

    }
  }

  function getorderxq ( $order_id = "" ) {
    $order        = Db::name( 'order' );
    $delivery_doc = Db::name( 'delivery_doc' );

    $where = [];

    $data = input();

    $shipping_code = '';

    $logisticCode = '';

    if ( $order_id ) {

      $orderid[ 'a.order_id' ] = [ 'exp', "IN ({$data['order_id']})" ];
    }

    $data = input();

    $list = $order->alias( 'a' )->join( "order_goods b ", 'a.`order_id`=b.`order_id`' )->join( "transport c", 'a.`shipping_id`=c.id' )->join( "goods d", 'b.`goods_id`=d.`id`' )->where( $where )->select();

    foreach ( $list as $k => $v ) {
      $shipping_code = $list[ $k ][ 'transport_code' ]; // 快递标识
      $logisticCode  = $list[ $k ][ 'invoice_no' ]; // 快递单号
    }
    $Shipping = new Shipping();
    $kdinfo   = $Shipping->getShipping( $shipping_code, $logisticCode );

    $this->assign( 'kdinfo', $kdinfo );

    $this->assign( 'orderlist', $list );

    return $this->fetch( 'ordersDetail' );
  }

  function getshipping ( $shipping_code = '', $logisticCode = '' ) {
    $data = input();

    $Shipping = new Shipping();
    $kdinfo   = $Shipping->getShipping( $data[ 'shipping_code' ], $data[ 'logisticCode' ] );


    $this->assign( 'kdinfo', $kdinfo );

    return $this->fetch( 'checkTransport' );
  }
}

?>
