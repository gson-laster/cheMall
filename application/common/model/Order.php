<?php
namespace app\common\model;
use app\api\controller\Motuijian;
use app\api\controller\Sms;
use app\common\Hook;
use app\home\controller\Weichat;
use function GuzzleHttp\json_encode;
use think\Model;
use think\Db;
use think\Session;
use think\Config;
use think\Log;

/**
 * 订单
 */
class Order extends Model
{
  //自定义初始化
  protected function initialize()
  {
      //需要调用`Model`的`initialize`方法
      parent::initialize();
  }

  /**
   * 订单详情
   * @param    [type]                   $orderId [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T15:38:28+080
   */
  public function getOrderInfo($orderId){
    return $this->find($orderId);
  }

  /**
   * 订单商品
   * @param    [type]                   $orderId [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T15:39:43+080
   */
  public function getOrderGoods($orderId){
    return Db::name('order_goods')->alias('og')->field(['og.*', 'g.thumb'])->join('__GOODS__ g', 'g.id=og.goods_id')->where("order_id=".$orderId)->select();
  }

  /*
   * 获取当前可操作的按钮
   */
  public function getOrderButton($order){
      /*
       *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
       *
       */
      adminLog('修改订单');
    $os = $order['order_status'];   //订单状态2
    $ss = $order['shipping_status'];//发货状态1
    $ps = $order['pay_status'];     //支付状态2
      $btn = array();
      if($order['paycode'] == 'cod') {
        if($os == 0 && $ss == 0){
          $btn['confirm'] = '确认';
        }elseif($os == 1 && $ss == 0 ){
          $btn['delivery'] = '去发货';
          $btn['cancel'] = '取消确认';
        }elseif($ss == 1 && $os == 1 && $ps == 0){
          $btn['pay'] = '付款';
        }elseif($ps == 1 && $ss == 1 && $os == 1){
          $btn['pay_cancel'] = '设为未付款';
        }
      }else{
        if($ps == 1 && $os == 1 || $ps == 1){
          $btn['pay'] = '付款';
        }elseif($os == 1 && $ps == 2){
          $btn['pay_cancel'] = '设为未付款';
          $btn['confirm'] = '确认';
        }elseif($os == 2 && $ps == 2 && $ss==1){
          $btn['cancel'] = '取消确认';
          $btn['delivery'] = '去发货';
        }
      }

      if($ss == 1 && $os == 1 && $ps == 1){
        $btn['delivery_confirm'] = '确认收货';
        $btn['refund'] = '申请退货';
      }elseif($os == 2 || $os == 4){
        $btn['refund'] = '申请退货';
      }elseif($os == 3 || $os == 5){
        $btn['remove'] = '移除';
      }
      if($os != 6){
        $btn['invalid'] = '无效';
      }
      return $btn;
  }

  /**
   * 订单操作记录，增加
   */
  public function orderActionLog($order_id,$action,$note='', $userId=0){
      $order = $this->find($order_id);
      $data['order_id'] = $order_id;
      $data['action_user'] = $userId ? $userId :session('admin_id');
      $data['action_note'] = $note;
      $data['order_status'] = $order['order_status'];
      $data['pay_status'] = $order['pay_status'];
      $data['shipping_status'] = $order['shipping_status'];
      $data['log_time'] = time();
      $data['status_desc'] = $action;
      return Db::name('order_action')->insert($data);//订单操作记录
  }

  /**
   * 更新订单
   * @param    [type]                   $order_id [description]
   * @param    [type]                   $act      [description]
   * @param    array                    $ext      [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T14:30:56+080
   */
  public function orderProcessHandle($order_id,$act,$ext=array()){
    //dump($act);
    $updata = $this->find($order_id);
    switch ($act){
      case 'pay': //付款
              $order_sn = $this->where("order_id = $order_id")->value("order_sn");
              update_pay_status($order_sn,$ext); //更新订单
        return true;
      case 'pay_cancel': //取消付款
        $updata->order_status = 1;
        $res = $this->order_pay_cancel($order_id);
        // dump($res);exit;
        break;
      case 'confirm': //确认订单
        $updata->order_status = 2;
        $msg = '确认订单';
        //dump(11);exit;
        break;
      case 'cancel': //取消确认
        $updata->order_status = 1;
        $msg = '取消确认订单';

        break;
      case 'invalid': //作废订单
        $updata->order_status = 6;
        $msg = '作废订单';

        break;
      case 'remove': //移除订单
        $this->delOrder($order_id);
        break;
      case 'delivery_confirm'://确认收货
        confirm_order($order_id); // 调用确认收货按钮
        $params = ['order_id' =>$order_id, 'userId' => $updata->user_id];
        Hook::call('Order', 'GetSuccess', $params);
        return true;
      default:
        return true;
    }
    // dump($updata->toArray());exit;
    $this->data($updata, true);
    $data = $updata->toArray();
    // unset($data['add_time']);
    // dump($data);
    $result = $this->where("order_id=$order_id")->update($updata->toArray());
    logOrder($order_id,$msg,$msg,0);
    return $result;//改变订单状态
  }

  //管理员取消付款
  function order_pay_cancel($order_id)
  {
      adminLog('取消付款');
    //如果这笔订单已经取消付款过了
    $count = $this->where("order_id = $order_id and pay_status = 2")->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
    if($count == 0) return false;
    // 找出对应的订单
    $order = $this->where("order_id = $order_id")->find();
    // 增加对应商品的库存
    $orderGoodsArr = Db::name('OrderGoods')->where("order_id = $order_id")->select();
    // dump(1);?
    $goodsDb = Db::name('Goods');
    foreach($orderGoodsArr as $key => $val)
    {
      changeStore($val['goods_id'], $val['goods_num'],$val['spec_key']);  //增加商品库存
      $goodsDb->where("id={$val['goods_id']}")->setDec('buy_num',$val['goods_num']); // 减少商品销售量
    }
    Db::name('order')->where("order_id={$order_id}")->update(array('pay_status'=>1,'order_status'=>1));
    // dump(Db::name('order')->getLastSql());exit;
    // update_user_level($order['user_id']);
    // 记录订单操作日志
    logOrder($order['order_id'],'订单取消付款','付款取消',0);
    // dump(2);exit;
    //分销设置
    // Db::name('rebate_log')->where("order_id = {$order['order_id']}")->save(array('status'=>0));
  }

  /**
   *	处理发货单
   * @param array $data  查询数量
   */
  public function deliveryHandle($datas){
    //dump(Config::get('shipping_config')[$datas['shipping_code']]);exit;
    adminLog('处理发货单');
    $order = $this->getOrderInfo($datas['order_id']);
    $orderGoods = $this->getOrderGoods($datas['order_id']);
    $selectgoods = $datas['goods'];
    $data['order_sn'] = $order['order_sn'];
    $data['order_id'] = $order['order_id'];
    $data['invoice_no'] = $datas['invoice_no'];
    $data['delivery_sn'] = $this->get_delivery_sn();
    $data['zipcode'] = $order['zipcode'];
    $data['user_id'] = $order['user_id'];
    $data['admin_id'] = Session::get('admin_id');
    $data['consignee'] = $order['consignee'];
    $data['mobile'] = $order['mobile'];
    $data['province'] = $order['province'];
    $data['city'] = $order['city'];
    $data['district'] = $order['district'];
    $data['address'] = $order['address'];
    $data['shipping_id'] = $order['shipping_id'];
    $data['shipping_code'] = $datas['shipping_code'];
    $data['shipping_name'] = Config::get('shipping_config')[$datas['shipping_code']];
    $data['shipping_price'] = $order['shipping_price'];
    $data['create_time'] = time();
    $did = Db::name('delivery_doc')->insert($data);
    $is_delivery = 0;
    // dump($orderGoods);
    $r = false;
    foreach ($orderGoods as $k=>$v){
      if($v['is_send'] == 1){
        $is_delivery++;
        $r = true;
      }
      if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
        $res['is_send'] = 1;
        $res['delivery_id'] = $did;
        $r = Db::name('order_goods')->where("rec_id=".$v['rec_id'])->update($res);//改变订单商品发货状态
        $is_delivery++;
      }
    }
    $updata['shipping_time'] = time();
    $updata['invoice_no'] = $datas['invoice_no'];
    $updata['shipping_code'] = $datas['shipping_code'];
    if($is_delivery == count($orderGoods)){
      $updata['shipping_status'] = 2;
    }else{
      $updata['shipping_status'] = 3;
    }
    $this->where("order_id=".$data['order_id'])->update($updata);//改变订单状态

    $params = ['order_id' => $data['order_id'],'user_id' => $data['user_id']];
    Hook::call('Order', 'SendSuccess', $params);
    // dump($thi?s->getLastSql());exit;
    $s = $this->orderActionLog($order['order_id'],'delivery',$datas['note']);//操作日志
    return ($s && $r) ? $data : false;
  }

  /**
   * 得到发货单流水号
   */
  public function get_delivery_sn()
  {
      /* 选择一个随机的方案 */
      // send_http_status('310');
      // mt_srand((double) microtime() * 1000000);
      return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
  }

  /**
   * 订单处理(原)
   * @author: slide
   * @param $order_res
   */
  public function order_handle($order_res, $order_no, callable $payEnd = NULL, $text = "余额支付"){
    // $orderMdl = new Order();
    $flag = false;
    Db::startTrans();
    try {
      //支付
      // dump($user_vb->user_vb);exit;
      $order_data[ 'order_id' ]     = $order_res[ 'order_id' ];
      $order_data[ 'order_status' ] = 2;
      $order_data[ 'user_note' ]    = '用户支付成功';
      $order_data['pay_name'] = $text;
      //更新订单表
      $order_data[ 'pay_time' ]   = time();
      $order_data[ 'pay_status' ] = 2;
      \Think\Log::write( 'update data' . json_encode( $order_data ), 'info' );
      $this->data( $order_data, true );
      $res = $this->allowField( true )->isUpdate( true )->save(); // 保存订单
      \Think\Log::write( 'update result' . $res, 'info' );
      //订单操作表
      //订单操作日志记录
      $order_action_data = [ 'order_id'        => $order_res[ 'order_id' ],   //订单号
                             'action_user'     => Session::get( 'qt_userId' ),   //订单操作人
                             'order_status'    => $order_data[ 'order_status' ],  //订单状态
                             'shipping_status' => 1,  //配送状态
                             'pay_status'      => $order_data[ 'pay_status' ],  //支付状态
                             'action_note'     => $order_data[ 'pay_status' ] == 2 ? '支付成功成功' : '支付失败', //操作备注
                             'log_time'        => time(), 'status_desc' => $order_data[ 'pay_status' ] == 2 ? '前台用户支付成功' : '前台用户支付成失败' ];
      $order_action      = Db::name( 'order_action' );
      $order_action_res  = $order_action->insert( $order_action_data );

      // 分成
      $order_goods = Db::name('order_goods')->alias('og')->field(['og.*','g.agent_config','g.store_count','title'])->join('__GOODS__ g', 'g.id=og.goods_id', 'LEFT')->where('order_id', $order_res['order_id'])->select();
      /*$order_goods_keys = implode(',', array_keys(convert_arr_key($order_goods, 'goods_id')));
      $goodsMdl = new Goods();
      $goods_agent_config = $goodsMdl->field(['id','agent_config', 'store_count','title'])->where("id IN ($order_goods_keys)")->select();*/
      $user_info = Db::name('user')->where('id',$order_res['user_id'])->find();

      //自己是代理往上3 否则 往上4
      $parent_agent_user = [];
      if($user_info['agent_type'] != 0){
        if($user_info['agent_type'] != 1){
          $parent_agent_user_arr = getUserFullPath($order_res['user_id'], 1, 4);  //上面三级用户
          array_push($parent_agent_user_arr, $user_info);
          $parent_agent_user = $parent_agent_user_arr;
        }
      }else{
        $parent_agent_user = getUserFullPath($order_res['user_id'], 1, 5);  //上面四级用户
      }

      $company_accout = Db::name('user')->where('is_employ_agent', 1)->find(); //公司账户

      \Think\Log::write('用户数据'.json_encode($parent_agent_user));
      L('商品信息'.json_encode($order_goods, JSON_UNESCAPED_UNICODE), 'info');
      // [{1:5,2:3,3:2, 4:1},{1:5,2:3,3:2, 4:1}]

      $order_divided_total = 0;
      foreach ($order_goods as $k => $v){

        if($v['store_count'] <= 0){
          return $this->ajax(4000, $v['title'].'库存不足，无法完成订单支付');
        }

        $temp_config = json_decode($v['agent_config'], true); // [1=>5, 2=>3,3=>2, 4=>1]

        $total_money = $user_info['agent_type'] !=0 ? number_format($temp_config[1] / 100, 2)  * $v['goods_num'] * $v['profit'] - number_format($temp_config[$user_info['agent_type']] / 100, 2) * $v['goods_num'] * $v['profit'] : number_format($temp_config[1]/100, 2) * $v['goods_num'] * $v['profit'];

        L('需要分成的总额'.$total_money);

        // 每个商品的利润
        $goods_profit = $v['profit'] * $v['goods_num'];

        $order_divided_total += $total_money;
        $divided_total = 0;
        foreach ($parent_agent_user as $user_key => $user_value){
          L('会员信息'.json_encode($user_value));

          // 当前的分成比例
          if($user_value['agent_type'] == 0){
            // $current_divide_commision = number_format($temp_config[3] / 100, 2); // 为0时，直接取3
            continue;
          } else{
            $current_divide_commision = number_format($temp_config[$user_value['agent_type']] / 100, 2);
          }

          // 分成大于总需要分成
          if($divided_total >= $total_money) break;

          $have = ($total_money - $divided_total);

          if($user_key > 0){

            if(($parent_agent_user[$user_key - 1]['agent_type'] != 0) && ($parent_agent_user[$user_key - 1]['agent_type'] < $user_value['agent_type'])) continue;

            $curr_money = $current_divide_commision * $v['goods_num'] * $v['profit'];

            L('当前指针'.($user_key));
            L('上一个用户'.json_encode($parent_agent_user[($user_key - 1)], true));
            L('当前用户'.json_encode($user_value));

            $pre_money = $parent_agent_user[($user_key - 1)]['agent_type'] != 0 ? number_format($temp_config[$parent_agent_user[($user_key - 1)]['agent_type']]/100, 2) * $v['goods_num'] * $v['profit'] : 0;

            // 用 代理的金额减去下一级已经分出去的金额 大于当前要分的，则分剩下的
            $temp_money = ($curr_money - $pre_money) < 0 ? ($curr_money - $pre_money) * -1 : ($curr_money - $pre_money);
            $temp_money = $temp_money > $have ? $have : $temp_money;

            accountLog($user_value['id'], $temp_money, 1, '下级购买商品分成');
            divideNote(['user_id'=> $user_value['id'], 'order_id'=>$order_res['order_id'],'commission'=>0, 'total_money' => $temp_money, 'level' => isset($user_value['level']) ? $user_value['level'] : 0,'type'=>1]);

            $divided_total += $temp_money;
            L('购物当前分成'.$temp_money, 'info');
            L('购物当前记录'.$curr_money, 'info');
            L('购物当前下一级记录'.$pre_money, 'info');
          }else{

            if($user_info['agent_type'] == 0 || ($user_value['agent_type'] > $user_info['agent_type'])) continue; // 如果当前用户的代理等级小于下单用户则跳过

            // 直接上级，分满
            $temp_money = ($user_info['agent_type'] !=0 ) ? ($current_divide_commision * $v['goods_num'] * $v['profit'] - number_format($temp_config[$user_info['agent_type']] / 100, 2) * $v['goods_num'] * $v['profit']) : $current_divide_commision * $v['goods_num'] * $v['profit'];

            accountLog($user_value['id'], $temp_money, 1, '下级购买商品分成');
            divideNote(['user_id'=> $user_value['id'], 'order_id'=>$order_res['order_id'],'commission'=>0, 'total_money' => $temp_money, 'level' => isset($user_value['level']) ? $user_value['level'] : 0,'type'=>1, 'order_info' => json_encode($order_res), 'user_info' => json_encode($user_info)]);

            $divided_total += $temp_money;
            L('购物当前分成'.$temp_money, 'info');
          }

          // 享受下级直接收入的5%
          Motuijian::diectReword($user_value['pid'], $temp_money, $user_info, $order_res);

        }

        $order_divided_total += $divided_total;
        //所有人都分完，多余进公司账户
        // 自己是代理的话需要减掉自己的折扣
        $supplus_money = $user_info['agent_type'] !=0 ?  ($goods_profit - $divided_total - number_format($temp_config[$user_info['agent_type']] / 100  * $v['goods_num'] * $v['profit'], 2 )) : $goods_profit - $divided_total;
        L('剩余金额'.$supplus_money);
        if($company_accout && $supplus_money){
          accountLog($company_accout['id'], $supplus_money, 1, '分成剩下的金额转入');
          divideNote(['user_id'=> $company_accout['id'], 'order_id'=>$order_res['order_id'],'commission'=>0, 'total_money' => $supplus_money, 'level' => 0, 'type'=>1, 'order_info' => json_encode($order_res), 'user_info' => json_encode($user_info)]);
        }else{
          //转入冻结表
          change_over_to_not_divide(1, $supplus_money);
        }
      }

      // 更改状态
      $goods_name = '';
      $goods_total = 0;
      foreach ($order_goods as $n => $m){
        changeStore($m['goods_id'], $m['goods_num'], $m['spec_key']);
        $goods_name .= $m['goods_name'];
        $goods_total += $m['goods_num'];
      }

      // 更改订单分成状态
      model('order')->where('order_id', $order_res['order_id'])->update(['is_distribut' => 1,'distribut_total' => $order_divided_total,'actual_distribut'=>$divided_total]);

      // 返还折扣
      $this->payendBackZk($order_res['order_id']);

      Db::commit();
      // 发送消息
      // 成功
      $params = ['order_id' => $order_res['order_id'], 'userId'=>$order_res['user_id']];
      Hook::call('Order', 'PaySuccess', $params);

      //      // 清楚缓存
      //      if(Cache::has($order_no)) {
      //        Cache::rm($order_no);
      //      }
      // 判断需不需要短信通知 && 模板消息通知
      $sms_add_order      = cache_data( 'site_config' )[ 'do_pay_success' ];
      $template_add_order = cache_data( 'site_config' )[ 'template_pay_success' ];
      // dump(Session::get($res));
      if ( $sms_add_order ) {
        // 短信通知
        $sms          = new Sms();
        $mobile       = Session::get( 'userInfo' )[ 'phone' ];
        $scren        = 'do_pay_success';
        $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_res[ 'order_sn' ], $order_res[ 'consignee' ] ] );
        if ( $send_sms_res ) {
          Log::write( '支付短信:' . $order_res[ 'order_sn' ] . '短信发送成功', 'info' );
        } else {
          Log::write( '支付短信:' . $order_res[ 'order_sn' ] . '短信发送失败', 'info' );
        }
      }
      $wechat           = new Weichat();
      $weichat_code     = ( isset( $order_res[ 'weichat_sn' ] ) && $order_res[ 'weichat_sn' ] != '' ) ? '微信订单号' . $order_res[ 'weichat_sn' ] : '';
      if ( $template_add_order ) {
        // 模板消息通知

        $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_res['order_id'] ] ), 'PAY_SUCCESS', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单支付成功!", $order_res[ 'total_amount' ], '付钱啦支付', "订单包含{$goods_total}件商品。", $order_res[ 'order_sn' ], $weichat_code, "我们将尽快给您安排发货，请及时注意物流信息。" ] );
        if ( $template_send_re ) {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送成功', 'info' );
        } else {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送失败', 'info' );
        }
      }

      // 发送消息模板消息给客服
      $kefu_uid = getUserInfoBywhere2(['phone'=>Config::get('shipping_kefu_account')], 'id');
      if($kefu_uid){
        $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
          '亲爱的发货专员商城有一笔新的订单成功支付,赶快去发货吧!','订单发货','待办','请尽快处理！'
        ], $kefu_uid['id']);
        if ( $template_send_kefu_res ) {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送成功', 'info' );
        } else {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送失败', 'info' );
        }
      }

      $flag = true;
    } catch ( \Exception $e ) {
      // 回滚事务
      \think\Log::write( '错误' . $e, 'error' );
      Db::rollback();

      $order_data[ 'pay_time' ]   = time();
      $order_data[ 'pay_status' ] = 3;
      //订单操作日志记录
      $order_action_data = [ 'order_id'        => $order_res[ 'order_id' ],   //订单号
                             'action_user'     => $order_res['user_id'],   //订单操作人
                             'order_status'    => $order_data[ 'order_status' ],  //订单状态
                             'shipping_status' => 1,  //配送状态
                             'pay_status'      => $order_data[ 'pay_status' ],  //支付状态
                             'action_note'     => $order_data[ 'pay_status' ] == 2 ? '支付成功成功' : '支付失败', //操作备注
                             'log_time'        => time(), 'status_desc' => $order_data[ 'pay_status' ] == 2 ? '前台用户支付成功' : '前台用户支付失败' ];
      $order_action      = Db::name( 'order_action' );
      $order_action_res  = $order_action->insert( $order_action_data );
      $flag              = false;
    }
    $callback = $flag ? ['code'=> 'success', 'backUrl' => url( "home/order/topay", [ "paycode" => request()->controller(), 'orderid' => $order_res['order_id'], 'res' => 'success' ]), 'order_res' => $order_res, 'user_info' => $user_info] : ['code' => 'error', 'backUrl' => url( "home/order/detail", [ "order_id" => $order_res['order_id'] ] ), 'order_res' => $order_res, 'user_info' => $user_info];

    !is_null($payEnd) && call_user_func($payEnd, $callback);
  }

  /**
   * 订单支付完成/分成/分红记录
   * @methods
   * @desc
   * @author slide
   *
   */
  public function orderPay ($order_res, $type = 'Remainder', callable $paying = null, callable $payEnd = null) {

    $flag = false;
    Db::startTrans();

    try{

      // 计算订单利润，然后分成
      $orderGoods = $this->getOrderGoods($order_res['order_id']);
      // 1、计算商品里利润
      $profit = 0;
      $goods_total = 0;
      foreach ($orderGoods as $k => $v) {
        $profit += $v['profit'];
        $goods_total += $v['goods_num'];
      }
      // 2、计算折扣
      $zk = $order_res['total_amount'] - $order_res['order_amount'];

      // 3、计算存利润
      $profit_res = $profit - $zk;
      L('折扣'.$zk);
      L('毛利润'.$profit);
      L('利润'.$profit_res);
      // dump($user_vb->user_vb);exit;
      $order_data[ 'order_id' ]     = $order_res[ 'order_id' ];
      $order_data[ 'order_status' ] = 2;
      $order_data[ 'user_note' ]    = '用户支付成功';
      switch ($type) {
        case 'Remainder':
          $text = "余额支付";
          break;
        case 'Weichat';
          $text = "微信支付";
        case 'Fuqianla':
          $text = "付钱啦支付";
      }
      $order_data['pay_name'] = $text;
      //更新订单表
      $order_data[ 'pay_time' ]   = time();
      $order_data[ 'pay_status' ] = 2;
      \Think\Log::write( 'update data' . json_encode( $order_data ), 'info' );
      $this->data( $order_data, true );
      $res = $this->allowField( true )->isUpdate( true )->save(); // 保存订单
      \Think\Log::write( 'update result' . $res, 'info' );
      //订单操作表
      //订单操作日志记录
      $order_action_data = [ 'order_id'        => $order_res[ 'order_id' ],   //订单号
                             'action_user'     => Session::get( 'qt_userId' ),   //订单操作人
                             'order_status'    => $order_data[ 'order_status' ],  //订单状态
                             'shipping_status' => 1,  //配送状态
                             'pay_status'      => $order_data[ 'pay_status' ],  //支付状态
                             'action_note'     => $order_data[ 'pay_status' ] == 2 ? '支付成功成功' : '支付失败', //操作备注
                             'log_time'        => time(), 'status_desc' => $order_data[ 'pay_status' ] == 2 ? '前台用户支付成功' : '前台用户支付成失败' ];
      $order_action      = Db::name( 'order_action' );
      $order_action_res  = $order_action->insert( $order_action_data );

      !is_null($paying) && call_user_func($paying, $order_res);

      // 返还折扣
      $this->payendBackZk($order_res['order_id']);
      if($profit_res <= 0) return true;
      $userInfo = (new User())->find($order_res['user_id']);

      // $this->shareRedAccount($profit_res, $order_res, $userInfo->toArray());

      Db::commit();
      $flag = true;
      $params = ['order_id' => $order_res['order_id'], 'userId'=>$order_res['user_id']];
      Hook::call('Order', 'PaySuccess', $params);

      //      // 清楚缓存
      //      if(Cache::has($order_no)) {
      //        Cache::rm($order_no);
      //      }
      // 判断需不需要短信通知 && 模板消息通知
      $sms_add_order      = cache_data( 'site_config' )[ 'do_pay_success' ];
      $template_add_order = cache_data( 'site_config' )[ 'template_pay_success' ];
      // dump(Session::get($res));
      if ( $sms_add_order ) {
        // 短信通知
        $sms          = new Sms();
        $mobile       = Session::get( 'userInfo' )[ 'phone' ];
        $scren        = 'do_pay_success';
        $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_res[ 'order_sn' ], $order_res[ 'consignee' ] ] );
        if ( $send_sms_res ) {
          Log::write( '支付短信:' . $order_res[ 'order_sn' ] . '短信发送成功', 'info' );
        } else {
          Log::write( '支付短信:' . $order_res[ 'order_sn' ] . '短信发送失败', 'info' );
        }
      }
      $wechat           = new Weichat();
      $weichat_code     = ( isset( $order_res[ 'weichat_sn' ] ) && $order_res[ 'weichat_sn' ] != '' ) ? '微信订单号' . $order_res[ 'weichat_sn' ] : '';
      if ( $template_add_order ) {
        // 模板消息通知

        $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_res['order_id'] ] ), 'PAY_SUCCESS', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单支付成功!", $order_res[ 'order_amount' ], '付钱啦支付', "订单包含{$goods_total}件商品。", $order_res[ 'order_sn' ], $weichat_code, "我们将尽快给您安排发货，请及时注意物流信息。" ] );
        if ( $template_send_re ) {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送成功', 'info' );
        } else {
          Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送失败', 'info' );
        }
      }

      /*// 发送消息模板消息给客服
      $kefu_uid = getUserInfoBywhere(['phone'=>Config::get('shipping_kefu_account')])[0];
      $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
        '亲爱的发货专员商城有一笔新的订单成功支付,赶快去发货吧!','订单发货','待办','请尽快处理！'
      ], $kefu_uid['id']);
      if ( $template_send_kefu_res ) {
        Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送成功', 'info' );
      } else {
        Log::write( '订单:' . $order_res[ 'order_sn' ] . '模板消息发送失败', 'info' );
      }*/

    }catch (\Exception $e){
      L('订单支付失败'.$e);
      Db::rollback();
      $order_data[ 'pay_time' ]   = time();
      $order_data[ 'pay_status' ] = 3;
      //订单操作日志记录
      $order_action_data = [ 'order_id'        => $order_res[ 'order_id' ],   //订单号
                             'action_user'     => $order_res['user_id'],   //订单操作人
                             'order_status'    => $order_data[ 'order_status' ],  //订单状态
                             'shipping_status' => 1,  //配送状态
                             'pay_status'      => $order_data[ 'pay_status' ],  //支付状态
                             'action_note'     => $order_data[ 'pay_status' ] == 2 ? '支付成功成功' : '支付失败', //操作备注
                             'log_time'        => time(), 'status_desc' => $order_data[ 'pay_status' ] == 2 ? '前台用户支付成功' : '前台用户支付失败' ];
      $order_action      = Db::name( 'order_action' );
      $order_action_res  = $order_action->insert( $order_action_data );
      $flag = false;
    }
    $callback = $flag ? ['code'=> 'success', 'backUrl' => url( "home/order/topay", [ "paycode" => request()->controller(), 'orderid' => $order_res['order_id'], 'res' => 'success' ])] : ['code' => 'error', 'backUrl' => url( "home/order/detail", [ "order_id" => $order_res['order_id'] ] )];

    !is_null($payEnd) && call_user_func($payEnd, $callback);
  }

  /**
   * 付款之后返还折扣
   * @methods
   * @desc
   * @author slide
   * @param $order_id
   *
   */
  public function payendBackZk($order_id){
    $order_res = $this->field('order_sn,user_id,order_amount,total_amount')->where('order_id', $order_id)->find();

    if(!$order_res) return ;

    // 返钱
    $total = $order_res['total_amount'] - $order_res['order_amount'];
    L('需要返还的金额'.$total);
    accountLog($order_res['user_id'], $total, 1, '您的订单'.$order_res['order_sn'].'折扣已经到账啦！！');
    L('返还状态');
    return true;
  }

  /**
   * 每笔计算分红补贴
   * @desc
   * @author slide
   */
  public function shareRedAccount($total_money = 0, $rder_info = [], $user_info = []) {

    $where['agent_type'] = ['<>', 0];
    $where['is_cansharered'] = 1;
    $user_agent_data = Db::name('user')->field('id,pid,phone,agent_type,is_cansharered,pid')->where($where)->select();

    // 处理代理数据
    $user_agent = [];
    $total_count = [
      1 => 0,
      2 => 0
    ];

    foreach ($user_agent_data as $v) {
      $user_agent[$v['agent_type']][] = $v;
      if(isset($total_count[$v['agent_type']])) {
        $total_count[$v['agent_type']] ++;
      }else{
        $total_count[$v['agent_type']] = 1;
      }
    }

    $agent_info = Db::name('agent')->select();
    $agent_info = convert_arr_key($agent_info, 'id');

    // 总共需要分出去的钱
    $total_need_divide_one = Config::get('share_red_one') * $total_money;
    $total_need_divide_two = Config::get('share_red_two') * $total_money;

    // 计算每个等级每个人可以分多少钱
    $need_divide = [
      'one' => $total_need_divide_one / ($total_count[1] + $total_count[2]),
      'two' => $total_need_divide_two / $total_count[2],
    ];

    // 分红
    Db::startTrans();
    try{

    foreach ($user_agent_data as $k => $v) {
      $total_money_user = $need_divide['one'];
      L('当前用户能分到的钱'.$total_money_user);


      if($v['agent_type'] == 1) {
        $total_money += $need_divide['two'];
        $agent_divide_total = Config::get('share_red_total_two');
      }else{
        $agent_divide_total = Config::get('share_red_total_one');
      }
      $divided_money = Db::name('share_red')->where('user_id', $v['id'])->find();
      L('分成信息'.var_export($divided_money, true));
      L('最大分成金额'.$agent_divide_total);

      // 判断用户已经分到的钱是否大于规定值
      if(isset($divided_money['share_red_total']) && $divided_money['share_red_total'] >= $agent_divide_total ||  $total_money_user <= 0) continue;
        divideNote([
          'user_id' => $v['id'],
          'order_id' => $order_info['order_id'],
          'commission' => 0,
          'total_money' => $total_money_user,
          'level' => 0,
          'type' => 3,
          'user_info' => json_encode($user_info),
          'order_info' => json_encode($order_info)
        ]);
        if($divided_money){
          Db::name('share_red')->where('user_id', $v['id'])->setInc('share_red_total', $total_money);
        }else{
          Db::name('share_red')->insert([
            'user_id'=> $v['id'],
            'share_red_total' => $total_money_user,
            'createtime' => time()
          ]);
        }
        accountLog($v['id'], $total_money_user, 1, '商城利润分红到账啦！');
    }

    Db::commit();
    }catch (\Exception $e) {
      L('分成错误'.$e);
      Db::rollback();
    }
  }
}

 ?>
