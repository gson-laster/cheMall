<?php
namespace app\home\controller;

use app\common\Hook;
use think\Session;
use think\Db;
use think\Log;
use think\Config;
use app\common\model\Shopcar;
use app\common\model\Spec;
use app\common\model\SpecItem;
use app\common\model\Transport;
use app\common\model\Payment;
use app\common\model\Address;
use app\common\model\User;
use app\common\model\Order as OrderModel;
use app\api\controller\Sms;
use app\home\controller\Weichat;
use app\api\controller\Shipping;

use app\api\controller\Message;
use think\Controller;

use app\api\controller\Base;

/**
 * 前台订单
 */
class Order extends Validate {

  protected $shopcarMdl;

  protected $orderMdl;

  protected function _initialize () {
    parent::_initialize();
    $this->shopcarMdl = new Shopcar();
    $this->orderMdl   = new OrderModel();
  }

  /**
   * 订单确认
   *
   * @param    [type] $shopids
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-14T15:16:29+080
   */
  public function index ( $shopids) {
    if ( !$shopids ) {
      $this->error( '请先选择商品', $_SERVER[ 'HTTP_REFERER' ] ); // 返回
    } else {
      $ids             = explode( ',', $shopids );
      $where[ 's.id' ] = [ 'exp', "IN ({$shopids})" ];
      $list            = $this->shopcarMdl->alias( 's' )->field( [ 's.id', 's.goods_id', 's.user_id', 's.artnum', 's.goods_price', 's.goods_name', 's.spec_config', 's.selected', 's.number', 'g.goods_type', 'g.unit','g.mkt_price' ] )->join( "__GOODS__ g", 'g.id=s.goods_id', 'LEFT' )->where( $where )->select();
      if ( !$list ) {
        return $this->error( '没有这样的商品' );
      }
//      dump($list);
      // 商品规格
      $goodsSpec  = new Spec();
      $goods_spec = $goodsSpec->field( [ 'id', 'name', 'type_id' ] )->order( 'zid DESC' )->select();
      // 算总价
      $totalPrice = 0;
      $totalNum   = 0;
      foreach ( $list as $key => &$value ) {
        $totalPrice       += (int) $value[ 'number' ] * (float) $value[ 'goods_price' ];
        $totalNum         += (int) $value[ 'number' ];
        $value[ 'thumb' ] = goods_thum_images( $value[ 'goods_id' ], 200, 200 );

        $temp_spec = [];
        foreach ( $goods_spec as $k => $v ) {
          if ( $value[ 'goods_type' ] == $v[ 'type_id' ] ) {
            $temp_spec[] = $v;
          }
        }

        $value[ 'spec' ] = $temp_spec;
        // dump($goods_spec);
        // 规格参数
        if ( $value[ 'spec_config' ] != '' ) {
          $goods_spec_item      = new SpecItem();
          $spec_item_ids        = explode( '|', $value[ 'spec_config' ] );
          $goods_spec_item_res  = $goods_spec_item->where( "id IN (" . implode( ',', $spec_item_ids ) . ") " )->select();
          $value[ 'spec_item' ] = $goods_spec_item_res;
        }
      }

      // dump($list);
      // 运费模板
      $transport        = new Transport();
      $transport_res    = $transport->where( "status=1" )->order( "zid desc" )->select();
      $transport_result = [];
      foreach ( $transport_res as $key => $vm ) {
        $temp               = [ "label" => $vm[ 'name' ], 'value' => $vm[ 'id' ] ];
        $transport_result[] = $temp;
      }

      // 支付方式
      $payment     = new Payment();
      $payment_res = $payment->field( [ 'id', 'name', 'desc', 'logo' ] )->where( "status=1" )->select();
      $pay_result  = [];
      foreach ( $payment_res as $k => $v ) {
        $temp         = [ 'label' => $v[ 'name' ], 'value' => $v[ 'id' ] ];
        $pay_result[] = $temp;
      }

      // 用户地址
      $address     = new Address();
      $address_res = $address->where( "uid=" . Session::get( 'qt_userId' ) )->select();
      // dump(convert_arr_key($address_res, 'id'));
      // 默认地址
      $user         = User::get( Session::get( 'qt_userId' ) );
      $address_now  = $user->address_now;
      $user_address = [];
      if ( $address_now ) {
        $user_address = $address_res ? convert_arr_key( $address_res, 'id' )[ $address_now ] : [];
      }

      // 单位
      $unit_arr = [ '', '件', '斤', 'KG', '吨', '套' ];

      return $this->fetch( 'index', [ 'list' => $list, 'transport' => $transport_result, 'payment' => $pay_result, 'address' => $address_res, 'user_address' => $user_address, 'unit_arr' => $unit_arr, 'totalPrice' => $totalPrice, 'totalNum' => $totalNum, 'ids' => $shopids ] );
    }
  }

  /**
   * 创建订单 (需要接受选中地址|支付方式|配送方式|购物车的ids)
   *
   * @Author   : slade
   * @DateTime :2017-04-14T09:52:08+080
   */
  public function add () {
    if ( $this->request->isPost() ) {
      // 接受数据并验证
      $data            = $this->request->post();
      $rule            = [ [ 'addressId', 'require', '没有选择收货地址' ], [ 'paymentId', 'require', '没有选择支付方式' ], [ 'transportId', 'require', '没有选择配送方式' ] ];
      $validate_result = $this->validate( $data, $rule );
      if ( $validate_result !== true ) {
        $this->error( $validate_result );
      }
      $address_id   = input( 'post.addressId' ) ? input( 'post.addressId' ) : -1;
      $payment_id   = input( 'post.paymentId' );
      $transport_id = input( 'post.transportId' ) ? input( 'post.transportId' ) : -1;
      $shopcar_ids  = input( 'post.ids' );

      // 收货地址信息
      $address      = new Address();
      $address_info = $address->find( $address_id );

      // 物流信息
      $transport     = new Transport();
      $transport_res = $transport->field( [ 'id', 'name' ] )->find( $transport_id );
      // dump($transport_res['name']);exit;

      // 支付方式
      $payment      = new Payment();
      $payment_info = $payment->field( [ 'id', 'name', 'paycode' ] )->find( $payment_id );

      // 购物车的信息
      $where[ 's.id' ] = [ 'exp', "IN ({$shopcar_ids})" ];
      $goods_list      = $this->shopcarMdl->alias( 's' )->field( [ 's.id', 's.goods_id', 's.user_id', 's.artnum', 's.goods_price', 's.goods_name', 's.spec_config', 's.selected', 's.number', 's.profit', 'g.thumb', 'g.goods_type', 'g.unit', 'g.give_integral', 'g.store_count'] )->join( "__GOODS__ g", 'g.id=s.goods_id', 'LEFT' )->where( $where )->select();
      if ( !$goods_list ) {
        return $this->ajax( 1003, '商品不存在', url( 'home/index/goodsList' ) );
      }
      // 商品规格
      $goodsSpec  = new Spec();
      $goods_spec = $goodsSpec->field( [ 'id', 'name', 'type_id' ] )->order( 'zid DESC' )->select();
      // //算总价
      $totalPrice      = 0;
      $totalNum        = 0;
      $goods_spec_item = new SpecItem();
      $goods_ids = '';
      $spec_config_arr = [];
      $order_price = 0;
      $shiiping_price = 0;
      foreach ( $goods_list as $key => &$value ) {

        if($value['store_count'] <= 0){
          return $this->ajax(4000, $value['goods_name'].'库存不足，无法完成下单');
        }

        $totalPrice += (int) $value[ 'number' ] * (float) $value[ 'goods_price' ];
        $totalNum   += (int) $value[ 'number' ];

        // 计算规格
        $spec_key_name = '';
        if ( $value[ 'spec_config' ] != '' ) {
          $spec_config_arr[$value['goods_id']] = $value[ 'spec_config' ];
          $temp_spec           = [];
          $spec_item_ids       = explode( '|', $value[ 'spec_config' ] );
          $goods_spec_item_res = convert_arr_key( $goods_spec_item->where( "id IN (" . implode( ',', $spec_item_ids ) . ") " )->select(), 'spec_id' );

          foreach ( $goods_spec as $k => $vl ) {
            if ( $value[ 'goods_type' ] == $vl[ 'type_id' ] ) {
              if ( isset( $goods_spec_item_res[ $vl[ 'id' ] ] ) ) {
                $spec_key_name .= $vl[ 'name' ] . ':' . json_decode( $goods_spec_item_res[ $vl[ 'id' ] ], true )[ 'item' ] . "&nbsp;&nbsp;";
              }
            }
          }
          $order_count = $this->countOrderPrice($value['goods_id'], $value[ 'spec_config' ], $value[ 'number' ], true, $address_info['province']);
          $order_price += $order_count['zk_price'];
          $shiiping_price += $order_count['shipping_price'];
          //dump($order_price);
        }else{
          $order_count = $this->countOrderPrice($value['goods_id'],'', $value[ 'number' ], true, $address_info['province']);
          $order_price += $order_count['zk_price'];
          $shiiping_price += $order_count['shipping_price'];
        }
        $value[ 'spec_key_name' ] = $spec_key_name;
        $goods_ids .= $value['id'].',';
      }
      // 生成订单号
      $order_sn = $this->createOrderSn();

      // 物流费用
      $shipping_free = $this->countPrice( $addressId = $address_id, $shippingId = $transport_id, $shopcarIds = $shopcar_ids, $return = "array" );
      // dump($shipping_free);
      // 用户信息
      $user_info = Db::name('user')->field(['parent_agent'])->find(Session::get('qt_userId'));

      // 启动事务
      Db::startTrans();
      try {
        // 订单信息
        $order_data = [
                        'order_sn'        => $order_sn,
                        'user_id' => Session::get( 'qt_userId' ),
                        'order_status' => 1, // 订单状态
                        'shipping_status' => 1, // 发货状态
                        'pay_status'      => 1, // 支付状态
                        'consignee'       => $address_info[ 'name' ], // 收货人
                        'province'        => $address_info[ 'province' ], // 省
                        'city'            => $address_info[ 'city' ], // 市
                        'twon'            => $address_info['town'],
                        'district'        => $address_info[ 'area' ], // 县区
                        'address'         => $address_info[ 'address_info' ], // 地址
                        'mobile'          => $address_info[ 'phone' ], // 电话
                        'shipping_name'   => '', // 物流名称
                        'shipping_id'     => 0, // 物流id
                        'pay_name'        => '', // 支付方式名称
                        'pay_id'          => '', // 支付方式id
                        'paycode'         => '',
                        'shipping_price'  => $shiiping_price, // 物流费用
                        'goods_price'     => $totalPrice,
                        'total_amount'    => $totalPrice, // 订单总价(真实付款 待返)
                        'order_amount'    => $order_price, // 应付金额（真实原价）
                        'add_time'        => time(), // 创建时间
                        'user_note'       => '下单成功',
                        'parent_agent'    => $user_info['parent_agent' ], //上级区域代理
        ];

        // 添加订单
        $order_db = Db::name( 'order' );
        // dump($order_data);exit;
        $order_res      = $order_db->insert( $order_data );
        $order_inset_id = $order_db->getLastInsID();
        Log::write( $order_res, 'debug', 'notice' );
        Log::write( '下单成功order_inset_id:' . $order_inset_id, 'debug', 'notice' );
        // 添加商品
        $order_goods_date_arr = [];
        $goods_names          = '';
        foreach ( $goods_list as $k => $v ) {
          // $spec_key_name = $goods_spec_res[$v['goods_type']]
          // 订单商品信息
          if ( $k < 2 ) {
            $goods_names .= $v[ 'goods_name' ] . '&nbsp;';
          }
          $order_goods_info = [ 'order_id'      => $order_inset_id, // 订单id
                                'goods_id'      => $v[ 'goods_id' ], // 商品id
                                'goods_name'    => $v[ 'goods_name' ], // 商品名称
                                'goods_sn'      => $v[ 'artnum' ], // 商品货号
                                'goods_price'   => $v[ 'goods_price' ], // 商品价格
                                'goods_total'   => ( ( $v[ 'goods_price' ] * 1 ) * ( $v[ 'number' ] * 1 ) ), // 单品总计
                                'goods_num'     => $v[ 'number' ], // 购买数量
                                'profit'        => $v[ 'profit' ], // 商品利润
                                'give_integral' => $v[ 'give_integral' ], // 购买商品赠送积分
                                'spec_key'      => $v[ 'spec_config' ], // 购买商品的规格
                                'spec_key_name' => $v[ 'spec_key_name' ], // 对应的中文名字
                                'bar_code'      => '', // 商品条码
                                'is_comment'    => 0, // 是否评价
                                'is_send'       => 0, 'createtime' => time() ]; // 是否发货
          // 加销售量
          Db::name( 'goods' )->where( "id", $v[ 'goods_id' ] )->setInc( 'buy_num', 1 );
          $order_goods_date_arr[] = $order_goods_info;
        }
        $order_goods_db  = Db::name( 'order_goods' );
        $order_goods_res = $order_goods_db->insertAll( $order_goods_date_arr );
        Log::write( '订单商品插入成功order_goods_res:' . $order_goods_db->getLastInsID(), 'debug', 'notice' );
        // 订单操作日志记录
        $order_action_data = [ 'order_id'        => $order_inset_id, // 订单号
                               'action_user'     => Session::get( 'qt_userId' ), // 订单操作人
                               'order_status'    => 1, // 订单状态
                               'shipping_status' => 1, // 配送状态
                               'pay_status'      => 1, // 支付状态
                               'action_note'     => '下单成功', // 操作备注
                               'log_time'        => time(), 'status_desc' => '前台用户下单成功' ];
        $order_action      = Db::name( 'order_action' );
        $order_action_res  = $order_action->insert( $order_action_data );
        Log::write( $order_action_res, 'debug', 'notice' );
        Log::write( '订单操作记录插入成功order_action_res:' . $order_action->getLastInsID(), 'debug', 'notice' );
        // 删除购物车，
        $delete_wh[ 'id' ] = [ 'exp', "IN (" . $shopcar_ids . ")" ];
        Db::name( 'shopcar' )->where( $delete_wh )->delete();

        // 提交事务
        Db::commit();
        $params = ['order_id' => $order_inset_id, 'userId' => Session::get('qt_userId')];
        Hook::call('Order', 'mutiAddSuccess', $params);
        Log::write( '生成记录:' . url( 'home/Order/toPay', [ 'paymentId' => $payment_id, 'orderid' => $order_inset_id ] ), 'debug', 'notice' );

        // 判断需不需要短信通知 && 模板消息通知
        $sms_add_order      = cache_data( 'site_config' )[ 'do_order_success' ];
        $template_add_order = cache_data( 'site_config' )[ 'template_add_order' ];
        $agent_id = $user_info[ 'parent_agent' ] ? $user_info[ 'parent_agent' ] : (Db::name('user')->where('is_employ_agent', 1)->value('id'));
        if ( $sms_add_order ) {
          // 短信通知
          $sms          = new Sms();
          $mobile       = Session::get( 'userInfo' )[ 'phone' ];
          $scren        = 'do_order_success';
          $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_sn ] );
          if ( $send_sms_res ) {
            Log::write( '订单:' . $order_sn . '短信发送成功', 'info' );
          } else {
            Log::write( '订单:' . $order_sn . '短信发送失败', 'info' );
          }
        }

        if ( $template_add_order ) {
          // 模板消息通知
          $wechat = new Weichat();
          //发送给用户
          $template_send_user_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_inset_id ] ), 'ADD_ORDER', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单创建成功!", $order_sn, $goods_names . "等{$totalNum}件商品...",  "为了可以让您早日收到商品，请及时登录平台支付！" ] );
          if ( $template_send_user_re ) {
            Log::write( '订单:' . $order_sn . '模板消息发送成功', 'info' );
          } else {
            Log::write( '订单:' . $order_sn . '模板消息发送失败', 'info' );
          }

          //消息处理
          $message = new Message();
          //发送给自己
          $message->sendMsg( '订单创建成功', "您有一笔订单创建成功!其中包含" . $goods_names . "等{$totalNum}件商品", -1, Session::get( 'qt_userId' ), 2, 1,true,false );
          //发送给区域代理
          if($agent_id){
            $template_send_re = $wechat->sendTemplateMsg( '', 'ADD_ORDER', [ "您有一笔团队订单创建成功!", $goods_names . "等{$totalNum}件商品...",$order_sn, "为了可以让您的客户早日收到商品，请及时登录管理平台协助支付！" ], $agent_id);
            if ( $template_send_re ) {
              Log::write( '订单:' . $order_sn . '区域代理模板消息发送成功', 'info' );
            } else {
              Log::write( '订单:' . $order_sn . '区域代理模板消息发送失败', 'info' );
            }

            //发送给区域代理
            $message->sendMsg( '团队订单创建成功', "您有一笔团队订单创建成功!其中包含" . $goods_names . "等{$totalNum}件商品", -1, $agent_id, 2, 1,true,false );

          }
        }

        L('返回前');

        $url = url( 'home/order/CashierDesk', [ 'order_id' => $order_inset_id ] );
        return $this->ajax( 1002, '下单成功', $url);
      } catch ( \Exception $e ) {
        // 回滚事务
        Db::rollback();
        // dump($e);exit;
        Log::write( '错误提示' . $e, 'debug', 'notice' );

        // Log::write('order_inset_id:'.$order_inset_id,'debug','notice');
        return $this->ajax( 1001, '下单失败，请重试！' );
      }
    } else {
      $this->error( '访问方式不正确' );
    }
  }

  /**
   * 单个商品订单确认
   * @author: slide
   * @param $goodsid
   * @return mixed
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   *
   */
  public function toaddnow($goodsid){

      $where[ 'id' ] = [ 'exp', "IN ({$goodsid})" ];
      $goods_list      = Db::name('goods')->where($where)->find();

    if (Session::get( 'qt_userId' )){
      // 用户地址
      $address     = new Address();
      $address_res = $address->where( "uid=" . Session::get( 'qt_userId' ) )->select();
      // dump(convert_arr_key($address_res, 'id'));
      // 默认地址
      $user         = User::get( Session::get( 'qt_userId' ) );
      $address_now  = $user->address_now;
      $user_address = [];
      //dump($user);
      if ( $address_now ) {
          $user_address = $address_res ? convert_arr_key( $address_res, 'id' )[ $address_now ] : [];
      }
      //单位
      $unit_arr = [ '', '件', '斤', 'KG', '吨', '套' ];
      return $this->fetch('order/addnow',[ 'address' => $address_res, 'user_address' => $user_address,'goodslist'=>$goods_list,'unit_arr'=> $unit_arr]);
    }else{

        $this->redirect(url('home/login/index'));
//        $this->('未登录！',url('home/login/index'));


      }


  }

  /**
   * 单个商品下单
   * @author: slide
   * @return \think\response\Json
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   *
   */
  public function setorderadd(){

      if ( $this->request->isPost() ) {
          // 接受数据并验证
          $data            = $this->request->post();
          $number = isset($data['number']) ? $data['number'] : 1;
          $rule            = [ [ 'addressId', 'require', '没有选择收货地址' ]];
          $validate_result = $this->validate( $data, $rule );
          if ( $validate_result !== true ) {
              $this->error( $validate_result );
          }
          $address_id   = input( 'post.addressId' );
          $goodsid=input('post.goodsid');

          // 收货地址信息
          $address      = new Address();
          $address_info = $address->find( $address_id );

          // 商品信息
          $where[ 'id' ] = [ 'exp', "IN ({$goodsid})" ];
          $goods_list      = Db::name('goods')->where($where)->find();

          if(!$goods_list){
              return $this->ajax( 1003, '商品不存在', url( 'home/index/goodsList' ) );
          }
          // 商品规格
          $goodsSpec  = new Spec();
          $goods_spec = $goodsSpec->field( [ 'id', 'name', 'type_id' ] )->order( 'zid DESC' )->select();
          // //算总价
          $totalPrice      = 0;
          $totalNum        = 0;
          $goods_spec_item = new SpecItem();

          if($goods_list['store_count'] <= 0){
              return $this->ajax(4000,$goods_list['goods_name'].'库存不足，无法完成下单');
          }

          $totalPrice =  (float) $goods_list[ 'shop_price'] * $number;
          $totalNum   = $number;

          // 计算规格
          $spec_key_name = '';
          if ( $goods_list[ 'spec_config' ] != '' && !is_null($goods_list[ 'spec_config' ]) && $goods_list[ 'spec_config' ] != null && $goods_list[ 'spec_config' ] != 'null') {
              $temp_spec           = [];
              $spec = explode('|$|', $goods_list[ 'spec_config' ]);
              if($spec != ''){
                $spec_item_ids      =  is_array($spec[0]) ? array_keys(json_decode($spec[0],true)) : '';
                L('$spec_item_ids'.$spec_item_ids);
                if($spec_item_ids != ''){
                  $goods_spec_item_res = convert_arr_key( $goods_spec_item->where( "id IN (" . implode( ',', $spec_item_ids ) . ") " )->select(), 'spec_id' );

                  foreach ( $goods_spec as $k => $vl ) {
                    if ( $goods_list[ 'goods_type' ] == $vl[ 'type_id' ] ) {
                      if ( isset( $goods_spec_item_res[ $vl[ 'id' ] ] ) ) {
                        $spec_key_name .= $vl[ 'name' ] . ':' . json_decode( $goods_spec_item_res[ $vl[ 'id' ] ], true )[ 'item' ] . "&nbsp;&nbsp;";
                      }
                    }
                  }
                }
              }
          }
          $goods_list[ 'spec_key_name' ] = $spec_key_name;

          // 生成订单号
          $order_sn = $this->createOrderSn();

          // 用户信息
          $user_info = Db::name('user')->field(['parent_agent'])->find(Session::get('qt_userId'));
          L('当前商品信息'.json_encode($goods_list, true));
          $order_price = $this->countOrderPrice($goods_list['id'], '',$totalNum, true, $address_info['province']);
          L('商品数量'.$totalNum);
          // 启动事务
          Db::startTrans();
          try {
              // 订单信息
              $order_data = [
                  'order_sn'        => $order_sn,
                  'user_id' => Session::get( 'qt_userId' ),
                  'order_status' => 1, // 订单状态
                  'shipping_status' => 1, // 发货状态
                  'pay_status'      => 1, // 支付状态
                  'consignee'       => $address_info[ 'name' ], // 收货人
                  'province'        => $address_info[ 'province' ], // 省
                  'city'            => $address_info[ 'city' ], // 市
                  'twon'            => $address_info['town'],
                  'district'        => $address_info[ 'area' ], // 县区
                  'address'         => $address_info[ 'address_info' ], // 地址
                  'mobile'          => $address_info[ 'phone' ], // 电话
                  'shipping_name'   => '', // 物流名称
                  'shipping_id'     => 0, // 物流id
                  'pay_name'        => '', // 支付方式名称
                  'pay_id'          => '', // 支付方式id
                  //'paycode'         => '', 'shipping_price' => $shipping_free[ 'shipping_free' ], // 物流费用
                  'goods_price'     => $totalPrice ,// 应付金额
                  'total_amount'    => $totalPrice,// 订单总价(真实付款)
                  'shipping_price'  => $order_price['shipping_price'],
                  'order_amount'    => $order_price['zk_price'], // 应付总价（真实原价）
                  'add_time'        => time(), // 创建时间
                  'user_note'       => '下单成功',
                  'parent_agent'    => $user_info['parent_agent' ], //上级区域代理
              ];

              // 添加订单
              $order_db = Db::name( 'order' );
              // dump($order_data);exit;
              $order_res      = $order_db->insert( $order_data );
              $order_inset_id = $order_db->getLastInsID();
              Log::write( $order_res, 'debug', 'notice' );
              Log::write( '下单成功order_inset_id:' . $order_inset_id, 'debug', 'notice' );
              // 添加商品
              $order_goods_date_arr = [];
              $goods_names          = $goods_list [ 'title' ];


              $order_goods_info = [
                  'order_id'      => $order_inset_id, // 订单id
                  'goods_id'      =>  $goods_list [ 'id' ], // 商品id
                  'goods_name'    =>  $goods_list [ 'title' ], // 商品名称
                  'goods_sn'      =>  $goods_list [ 'artnum' ], // 商品货号
                  'goods_price'   =>  $goods_list [ 'shop_price' ], // 商品价格
                  'goods_total'   => ( (  $goods_list [ 'shop_price' ] * 1 ) * 1), // 单品总计
                  'goods_num'     =>  $number, // 购买数量
                  'profit'        =>  $goods_list [ 'store_profit' ], // 商品利润
                  'give_integral' =>  $goods_list [ 'give_integral' ], // 购买商品赠送积分
                  'spec_key'      =>  is_null($goods_list [ 'spec_config' ]) ? '' : $goods_list[ 'spec_config' ], // 购买商品的规格
                  'spec_key_name' =>  $goods_list [ 'spec_key_name' ], // 对应的中文名字
                  'bar_code'      => '', // 商品条码
                  'is_comment'    => 0, // 是否评价
                  'is_send'       => 0, 'createtime' => time() ]; // 是否发货
              // 加销售量
              Db::name( 'goods' )->where( "id", $goods_list[ 'id' ] )->setInc( 'buy_num', 1 );
              $order_goods_date_arr[] = $order_goods_info;

              $order_goods_db  = Db::name( 'order_goods' );
              $order_goods_res = $order_goods_db->insertAll( $order_goods_date_arr );
              Log::write( '订单商品插入成功order_goods_res:' . $order_goods_db->getLastInsID(), 'debug', 'notice' );
              // 订单操作日志记录
              $order_action_data = [ 'order_id'        => $order_inset_id, // 订单号
                  'action_user'     => Session::get( 'qt_userId' ), // 订单操作人
                  'order_status'    => 1, // 订单状态
                  'shipping_status' => 1, // 配送状态
                  'pay_status'      => 1, // 支付状态
                  'action_note'     => '下单成功', // 操作备注
                  'log_time'        => time(), 'status_desc' => '前台用户下单成功' ];
              $order_action      = Db::name( 'order_action' );
              $order_action_res  = $order_action->insert( $order_action_data );
              Log::write( $order_action_res, 'debug', 'notice' );
              Log::write( '订单操作记录插入成功order_action_res:' . $order_action->getLastInsID(), 'debug', 'notice' );
              // 删除购物车，
              //  $delete_wh[ 'id' ] = [ 'exp', "IN (" . $shopcar_ids . ")" ];
              //   Db::name( 'shopcar' )->where( $delete_wh )->delete();

              // 提交事务
              Db::commit();
              $params = ['order_id' => $order_inset_id, 'userId' => Session::get('qt_userId')];
              Hook::call('Order', 'oneAddSuccess', $params);
              Log::write( '生成记录:' . url( 'home/Order/toPay', [ 'orderid' => $order_inset_id ] ), 'debug', 'notice' );

              // 判断需不需要短信通知 && 模板消息通知
              $sms_add_order      = cache_data( 'site_config' )[ 'do_order_success' ];
              $template_add_order = cache_data( 'site_config' )[ 'template_add_order' ];
              $agent_id = $user_info[ 'parent_agent' ] ? $user_info[ 'parent_agent' ] : (Db::name('user')->where('is_employ_agent', 1)->value('id'));
              if ( $sms_add_order ) {
                  // 短信通知
                  $sms          = new Sms();
                  $mobile       = Session::get( 'userInfo' )[ 'phone' ];
                  $scren        = 'do_order_success';
                  $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_sn ] );
                  if ( $send_sms_res ) {
                      Log::write( '订单:' . $order_sn . '短信发送成功', 'info' );
                  } else {
                      Log::write( '订单:' . $order_sn . '短信发送失败', 'info' );
                  }
              }

              if ( $template_add_order ) {
                  // 模板消息通知
                  $wechat = new Weichat();
                  //发送给用户
                  $template_send_user_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_inset_id ] ), 'ADD_ORDER', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单创建成功!", $order_sn, $goods_names . "等{$totalNum}件商品...", "为了可以让您早日收到商品，请及时登录平台支付！" ] );
                  if ( $template_send_user_re ) {
                      Log::write( '订单:' . $order_sn . '模板消息发送成功', 'info' );
                  } else {
                      Log::write( '订单:' . $order_sn . '模板消息发送失败', 'info' );
                  }

                  //发送给区域代理
                  if($agent_id){
                      $template_send_re = $wechat->sendTemplateMsg( '', 'ADD_ORDER', [ "您有一笔团队订单创建成功!", $goods_names . "等{$totalNum}件商品...",$order_sn, "为了可以让您的客户早日收到商品，请及时登录管理平台协助支付！" ], $agent_id);
                      if ( $template_send_re ) {
                          Log::write( '订单:' . $order_sn . '区域代理模板消息发送成功', 'info' );
                      } else {
                          Log::write( '订单:' . $order_sn . '区域代理模板消息发送失败', 'info' );
                      }
                  }
              }

            //消息处理
            $message = new Message();
            //发送给自己
            $message->sendMsg( '订单创建成功', "您有一笔订单创建成功!其中包含" . $goods_names . "等{$totalNum}件商品", -1, Session::get( 'qt_userId' ), 2, 1,true,false );

              //发送给区域代理
            $message->sendMsg( '团队订单创建成功', "您有一笔团队订单创建成功!其中包含" . $goods_names . "等{$totalNum}件商品", -1, $agent_id, 2, 1,true,false);

              L('返回前');
              $url = url( 'home/order/CashierDesk', [ 'order_id' => $order_inset_id ] );
              return $this->ajax( 1002, '下单成功', $url);
          } catch ( \Exception $e ) {
              // 回滚事务
              Db::rollback();
              // dump($e);exit;
              Log::write( '错误提示' . $e, 'debug', 'notice' );

              // Log::write('order_inset_id:'.$order_inset_id,'debug','notice');
              return $this->ajax( 1001, '下单失败，请重试！' );
          }
      } else {
          $this->error( '访问方式不正确' );
      }
  }

  /**
   * 根据code发起支付
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-17T12:04:56+080
   */
  public function toPay ( $paycode, $orderid, $res = "topay" ) {
    if ( !$paycode || !$orderid ) {
      $this->error( '缺少必要参数' );
    }
    if($res == 'topay'){
      $params = ['order_id' => $orderid,'userId'=>Session::get('qt_userId'),'payCode'=>$paycode];
      Hook::call('Order', 'PayBeagin', $params);
    }
    // 组织支付地址
    if ( $res == 'success' ) {

      $order_goods = $this->orderMdl->getOrderGoods( $orderid );
      $order_info  = $this->orderMdl->getOrderInfo( $orderid );
      $goods_name  = '';
      $goods_total = 0;
      foreach ( $order_goods as $k => $v ) {
        // dump($v);
        changeStore( $v[ 'goods_id' ], $v[ 'goods_num' ], $v[ 'spec_key' ] ); // 减少库存
        if ( $k < 2 ) {
          $goods_name .= $v[ 'goods_name' ];
        }
        $goods_total += $v[ 'goods_num' ];
        // exit;
      }
      // 判断需不需要短信通知 && 模板消息通知
      $sms_add_order      = cache_data( 'site_config' )[ 'do_pay_success' ];
      $template_add_order = cache_data( 'site_config' )[ 'template_pay_success' ];
      // dump(Session::get($res));
      if ( $sms_add_order ) {
        // 短信通知
        $sms          = new Sms();
        $mobile       = Session::get( 'userInfo' )[ 'phone' ];
        $scren        = 'do_pay_success';
        $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_info[ 'order_sn' ], $order_info[ 'consignee' ] ] );
        if ( $send_sms_res ) {
          Log::write( '支付短信:' . $order_info[ 'order_sn' ] . '短信发送成功', 'info' );
        } else {
          Log::write( '支付短信:' . $order_info[ 'order_sn' ] . '短信发送失败', 'info' );
        }
      }

      if ( $template_add_order ) {
        // 模板消息通知
        $wechat           = new Weichat();
        $weichat_code     = ( isset( $order_info[ 'weichat_sn' ] ) && $order_info[ 'weichat_sn' ] != '' ) ? '微信订单号' . $order_info[ 'weichat_sn' ] : '';
        $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $orderid ] ), 'PAY_SUCCESS', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单支付成功!", $order_info[ 'order_amount' ], $order_info[ 'pay_name' ], "订单包含" . $goods_name . "等{$goods_total}件商品。", $order_info[ 'order_sn' ], $weichat_code, "我们将尽快给您安排发货，请及时注意物流信息。" ] );
        if ( $template_send_re ) {
          Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送成功', 'info' );
        } else {
          Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送失败', 'info' );
        }
      }
      $address_info =[
        'consignee' => $order_info['consignee'],
        'province'  => $order_info['province'],
        'city'      => $order_info['city'],
        'district'  => $order_info['district'],
        'address'   => $order_info['address'],
        'twon'      => $order_info['twon'],
        'mobile'    => $order_info['mobile']
      ];
      $this->assign('address_info', $address_info);
    }
    $url = "/home/" . strtolower( $paycode ) . "/topay/?backurl=/home/order/toPay/paycode/" . $paycode . "/orderid/" . $orderid . "&orderId=" . $orderid;
    $this->assign( 'toPay', $url );
    // dump($url);exit;
    $this->assign( 'order_id', $orderid );
    $this->assign( 'res', $res );

    return $this->fetch();
  }

  /**
   * 创建订单号
   *
   * @param    [type] $data
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-17T12:05:21+080
   */
  public function createOrderGoodsInfo ( $data ) {
    $goods_info = Db::name( 'order_goods' );
    $goods_res  = $goods_info->insert( $data );
  }

  /**
   * 生成订单号
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-13T11:08:03+080
   */
  protected function createOrderSn () {
    $order_id = '';
    while ( true ) {
      // 订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
      $order_id_main = date( 'YmdHis' ) . rand( 10000000, 99999999 );
      // 订单号码主体长度
      $order_id_len = strlen( $order_id_main );
      $order_id_sum = 0;
      for ( $i = 0; $i < $order_id_len; $i++ ) {
        $order_id_sum += (int) ( substr( $order_id_main, $i, 1 ) );
      }
      // 唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
      $order_id = "E" . $order_id_main . str_pad( ( 100 - $order_id_sum % 100 ) % 100, 2, '0', STR_PAD_LEFT );

      return $order_id;
    }
  }

  /**
   * ajax获取运费模版的地区和配置
   *
   * @param    [type] $transportid
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-14T16:12:53+080
   */
  public function getshippingarea ( $transportid ) {
    if ( $transportid ) {
      $res = Db::name( 'shipping_area' )->where( "transport_id={$transportid}" )->select();
      if ( $res ) {
        return $this->ajax( 1002, '查询成功', '', $res );
      } else {
        return $this->ajax( 1001, '查询失败' );
      }
    } else {
      $this->error( '请选择运费方式' );
    }
  }

  /**
   * 计算费用
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-14T17:24:53+080
   */
  public function countPrice ( $addressId = 0, $shippingId = 0, $shopcarIds = '', $return = 'json' ) {
    if ( !$shippingId || !$shopcarIds || !$addressId ) {
      return $this->ajax( 1003, '缺少必要参数' );
    }
    // 收货地址
    $address     = Db::name( 'address' );
    $address_res = $address->find( $addressId );

    // 配送方式收货地区
    $shipping  = Db::name( 'shipping_area' );
    $area_info = $shipping->where( "transport_id={$shippingId}" )->select();

    // 购物车的信息
    $shop_car          = Db::name( 'shopcar' );
    $where[ 'car.id' ] = [ 'exp', "IN ({$shopcarIds})" ];
    $shop_car_res      = $shop_car->alias( 'car' )->field( [ 'car.goods_id', 'car.number', 'car.goods_price', 'g.weight' ] )->join( "__GOODS__ g", 'g.id=car.goods_id' )->where( $where )->select();

    // 计算总重量
    $totalWeight = 0;
    $totalPrice  = 0;
    $totalNum    = 0;
    foreach ( $shop_car_res as $key => $value ) {
      $totalWeight += $value[ 'weight' ] * $value[ 'number' ] * 1000;
      $totalNum    += $value[ 'number' ];
      $totalPrice  += $value[ 'number' ] * $value[ 'goods_price' ];
    }

    // 循环每个地区里的地区配置
    $gd_price = 0;
    foreach ( $area_info as $k => $v ) {
      $temp_config_arr = json_decode( $v[ 'config' ], true );
      // dump($temp_config_arr);
      if ( $v[ 'is_default' ] != 1 ) {
        $temp_area = explode( '|', $temp_config_arr[ 'areaId' ] );
        if ( in_array( $address_res[ 'town' ], $temp_area ) ) { // 镇
          $gd_price = $temp_config_arr[ 'first_money' ]; // 首重的费用
          $gd_price += ( ( $totalWeight - $temp_config_arr[ 'first_weight' ] ) / $temp_config_arr[ 'second_weight' ] ) * $temp_config_arr[ 'second_money' ];
          break;
        }
        if ( in_array( $address_res[ 'area' ], $temp_area ) ) { // 地区、县
          $gd_price = $temp_config_arr[ 'first_money' ]; // 首重的费用
          $gd_price += ( ( $totalWeight - $temp_config_arr[ 'first_weight' ] ) / $temp_config_arr[ 'second_weight' ] ) * $temp_config_arr[ 'second_money' ];
          break;
        }
        if ( in_array( $address_res[ 'city' ], $temp_area ) ) { // 市
          $gd_price = $temp_config_arr[ 'first_money' ]; // 首重的费用
          $gd_price += ( ( $totalWeight - $temp_config_arr[ 'first_weight' ] ) / $temp_config_arr[ 'second_weight' ] ) * $temp_config_arr[ 'second_money' ];
          break;
        }
        if ( in_array( $address_res[ 'province' ], $temp_area ) ) { // 省
          $gd_price = $temp_config_arr[ 'first_money' ]; // 首重的费用
          $gd_price += ( ( $totalWeight - $temp_config_arr[ 'first_weight' ] ) / $temp_config_arr[ 'second_weight' ] ) * $temp_config_arr[ 'second_money' ];
          break;
        }
      } else {
        $gd_price = $temp_config_arr[ 'first_money' ]; // 首重的费用
        $gd_price += ( ( $totalWeight - $temp_config_arr[ 'first_weight' ] ) / $temp_config_arr[ 'second_weight' ] ) * $temp_config_arr[ 'second_money' ];
      }
    }
    $goods_total_price = $totalPrice + $gd_price;
    $shipping_price    =  0;//$goods_total_price >= cache_data( 'site_config' )[ 'noshipping' ] ? 0 : $gd_price;
    $last_result       = [ 'totalWeight' => $totalWeight, 'goods_total_price' => $totalPrice, 'totalPrice' => $goods_total_price, 'shipping_free' => $shipping_price, 'totalNum' => $totalNum ];

    return $return == 'array' ? $last_result : $this->ajax( 1002, '费用计算成功', '', $last_result );
  }

  /**
   * 最新计算订单价格
   * @author: slide
   * @param string $goods_ids
   * @param string $spec_ids
   * @param int    $num
   * @param bool   $need_chajia
   * @param int    $province     省级id
   * @param boolean $realPrice 是否需要返回真实价格
   * @return array|int
   *
   */
  public function countOrderPrice($goods_ids = '', $spec_ids = '', $num = 0, $need_chajia = false, $province=0){
    L('参数'.$goods_ids."_".$num);
    if(!$goods_ids || $goods_ids=='' || !$num || $num == 0) return 0;
    $goods_info = Db::name('goods')->where("id IN ($goods_ids)")->select();
    $is_agent = Session::get('userInfo')['agent_type'];
    $chajia = 0;
    $total_price = 0;
    $zk_price = 0;
    L('费用计算商品信息'.json_encode($goods_info));
    foreach ($goods_info as $k => $v){
      if($spec_ids != ''){
        $goods_config = json_decode($v['spec_config'], true);
        if(isset($goods_config[$spec_ids])){
          $price = $goods_config[$spec_ids]['price'];
          $total_price = $price * $num;
        }else{
          $total_price = $v['shop_price'] * $num;
        }
      }else{
        $total_price = $v['shop_price'] * $num;
      }

      if($is_agent){
        $goods_agent_config = json_decode($v['agent_config'], true);
        $chajia = number_format($total_price * ($goods_agent_config[$is_agent] / 100), 2);
      }

      $zk_price = $total_price - $chajia;
    }

    // 运费
    $shipping_price = 0;
    if(in_array($province, Config::get('Shipping')['province'])){
      $shipping_price = Config::get('Shipping')['price'] * $num;
      $total_price += $shipping_price;
    }

    if($need_chajia){
      return ['total_price'=>$total_price, 'chajia' => $chajia, 'shipping_price' => $shipping_price,'zk_price' => $zk_price];
    }else{
      return $total_price;
    }
  }

  /**
   * 购物车订单总价计算
   * @author: slide
   * @param $shopcar_ids
   *
   */
  public function countShopcarGoodsPrice($shopcar_ids, $province=0){
    $shopcar_goods = Db::name('shopcar')->field(['goods_id', 'number','spec_config'])->where("id IN ({$shopcar_ids})")->select();

    $total_price = 0;
    $chajia = 0;
    $number = 0;

    foreach ($shopcar_goods as $k => $v){
      $temp_price = $this->countOrderPrice($v['goods_id'], $v['spec_config'], $v['number'], true);
      $total_price += $temp_price['total_price'];
      $chajia += $temp_price['chajia'];
      $number += $v['number'];
    }

    // 运费
    $shipping_price = 0;
    if(in_array($province, Config::get('Shipping')['province'])){
      $shipping_price = Config::get('Shipping')['price'] * $number;
      $total_price += $shipping_price;
    }
    return ['total_price'=>$total_price, 'chajia' => $chajia, 'shipping_price' => $shipping_price];
  }

  /**
   * 查询订单状态数
   *
   * @param string $type
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-20T17:54:26+080
   */
  public function ajaxCount () {
    $userid = Session::get( 'qt_userId' );
    // 未支付
    $map             = "user_id={$userid}" . Config::get( 'WAITPAY' );
    $count[ 'NPAY' ] = $this->orderMdl->where( $map )->where( $userid )->count();

    // 未发货
    $where            = "user_id={$userid}" . Config::get( 'WAITSEND' );
    $count[ 'NSEND' ] = $this->orderMdl->where( $where )->where( $userid )->count();

    // 待收货
    $mp              = "user_id={$userid}" . Config::get( 'WAITRECEIVE' );
    $count[ 'NGET' ] = $this->orderMdl->where( $mp )->where( $userid )->count();
    // dump($this->orderMdl->getLastSql());

    // 待评价
    $np              = "user_id={$userid}" . Config::get( 'WAITCCOMMENT' );
    $count[ 'NCOM' ] = $this->orderMdl->where( $np )->where( $userid )->count();
    $this->success( '查询成功', '', [ 'num' => $count ] );
  }

  /**
   * 订单详情查询
   *
   * @param string $order_id
   *
   * @return mixed
   */
  function detail ( $order_id = "" ) {
    $order = Db::name( 'order' );
    $where = [];

    $data = input();

    //$shipping_code = '';

    //$logisticCode = '';

    $list = $order->alias( 'a' )->join( "order_goods b ", 'a.`order_id`=b.`order_id`' )->find( $data[ 'order_id' ] );

    //$shipping_code = $list[ 'transport_code' ]; // 快递标识
    //$logisticCode  = $list[ 'invoice_no' ]; // 快递单号


    $goods_where[ 'n.order_id' ] = $order_id;

    $order_goods = Db::name( 'order_goods' )->alias( 'n' )->where( $goods_where )->select();

    foreach ( $order_goods as $key => &$value ) {
      $value[ 'thumb' ] = goods_thum_images( $value[ 'goods_id' ], 200, 200 );
    }


     // $Shipping = new Shipping();
      //$kdinfo   = $Shipping->getShipping( $shipping_code, $logisticCode );

     // $this->assign( 'kdinfo', $kdinfo );

      $this->assign( 'orderlist', $list );

      $this->assign( 'goods', $order_goods );

      return $this->fetch( 'ordersDetail' );

  }

  /***
   * 查询物流信息
   *
   * @param string $shipping_code
   * @param string $logisticCode
   *
   * @return mixed
   */

  function getshipping ( $shipping_code = '', $logisticCode = '' ) {
    $data = input();

    $Shipping = new Shipping();
    $kdinfo   = $Shipping->getShipping( $data[ 'shipping_code' ], $data[ 'logisticCode' ] );

    // dump($kdinfo);
    // exit;

    $kdinfo[ 'shipping_name' ] = Db::name( 'transport' )->where( "transport_code", $data[ 'shipping_code' ] )->value( 'name' );

    $this->assign( 'kdinfo', $kdinfo );

    // $kdinfo = $this->jsonDatacount()->toArray();
    $this->assign( 'kdinfo', $kdinfo );

    return $this->fetch( 'checkTransport' );
  }

  /**
   * 用户取消或者收货
   *
   * @param string $order_id
   *            [description]
   * @param string $order_status
   *            [description]
   * @param string $action
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-04-25T11:33:16+080
   */
  function cancel ( $order_id = "", $order_status = "", $action = "", $goods_id = "", $spec_key = "" ) {
    $data = input();

    if ( $order_id ) {
      $order_id = $data[ 'order_id' ];
    }
    if ( $order_status ) {
      $order_status = $data[ 'order_status' ];
    }
    if ( $action ) {
      $action = $data[ 'action' ];
    }
    $result = $this->orderMdl->allowField( true )->isUpdate( true )->save( $data );
    if ( $result != 0 ) {
      $userId = Session::get( 'qt_userId' );
      if ( $data[ 'order_status' ] == 4 ) {
        $goods_id = $data[ 'goods_id' ];
        $spec_key = $data[ 'spec_key' ];
        changeStore( $goods_id, -1, $spec_key );
      }
      if ( $data[ 'order_status' ] == 3 ) {
        Db::name( 'order' )->where( 'order_id', $order_id )->update( [ 'confirm_time' => [ 'exp', time() ], ] );
        //积分
        $order_goods_res = Db::name( 'order_goods' )->field( [ 'goods_id' ] )->where( 'order_id', $order_id )->select();
        $total_record    = Db::name( 'goods' )->where( "id IN (" . ( implode( ',', array_column( $order_goods_res, 'goods_id' ) ) ) . ")" )->sum( 'give_integral' );
        Db::name( 'user' )->where( "id", Session::get( 'qt_userId' ) )->setInc( 'record', $total_record );
         $template_confirm_goods = cache_data( 'site_config' )[ 'template_confirm_goods' ];
        $order_info=Db::name('order')->alias( 'a' )->field(['a.*','b.goods_name'])->join("__ORDER_GOODS__ b ", 'b.order_id=a.order_id')->where('a.order_id', $order_id)->find();

        L('1034订单数据'.json_encode($order_info, true));
        //dump($order_info);exit;
        if ( $template_confirm_goods ) {
            // 模板消息通知
            $wechat           = new Weichat();
            $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_id ] ), 'CONFIRM_GOODS', [ "您在" . Config::get( 'SMS_SIGN' ) . "买的宝贝已经确认收货!", $order_info[ 'order_sn' ], $order_info[ 'goods_name' ], date("Y-m-d H:i:s", $order_info[ 'add_time' ]),date("Y-m-d H:i:s",$order_info[ 'shipping_time' ]),date("Y-m-d H:i:s", $order_info[ 'confirm_time' ]),  "感谢您的支持与厚爱。" ] );
            if ( $template_send_re ) {
                Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送成功', 'info' );
            } else {
                Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送失败', 'info' );
            }
        }
      }

      $this->orderMdl->orderActionLog( $order_id, $action, $userId );

      $this->success( "操作成功！", url( 'detail', [ 'order_id' => $order_id ] ) );
    } else {

      $this->error( "操作失败！", url( 'detail', [ 'order_id' => $order_id ] ) );
    }
  }

  /**
   * 收银台
   * @author: slide
   *
   */
  public function CashierDesk ($order_id){
    $need_pay = Db::name('order')->field(['order_id','order_amount','total_amount'])->where('order_id', $order_id)->find();
    $user_vb = Db::name('user')->where('id', Session::get('qt_userId'))->value('user_vb');

    $need_pay['user_vb'] = $user_vb;

    return $this->fetch('cashier_desk',['need_pay'=>$need_pay]);
  }

}



