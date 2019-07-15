<?php
namespace app\agentadmin\controller;

use think\Session;
use think\Config;
use think\Db;
use app\common\model\Order as OrderModel;
use app\common\model\Goods;
use app\api\controller\Sms;
use app\home\controller\Weichat;
use think\Log;
/**
 * 代理订单处理
 */
class Order extends Common
{
  protected $orderMdl;
  protected $agent_id;
  protected $agent_info;
  protected function _initialize(){
    parent::_initialize();
    $this->orderMdl = new OrderModel();
    if($this->isAgentAdmin()){
      $this->agent_id = Session::get('agent_admin')['id'];
    }else{
      $this->agent_id = Session::get('agent_employee')['agent_id'];
    }
    $this->agent_info = Db::name('user')->find($this->agent_id);
  }

  /**
   * 订单管理
   * @param    string                   $type    [查询条件   待支付（WAITPAY）|待发货（WAITSEND）|待收货（WAITRECEIVE）|已完成（FINISH）已付款(PAYEND)]
   * @param    string                   $keyword [查询条件   订单号|用户id]
   * @param    string                   $time    [查询条件   时间（当天）]
   * @param    integer                  $page    [description]
   * @Author:  slade
   * @DateTime :2017-05-14T15:50:15+080
   */
  public function NeedPayOrderList($type = '', $keyword = '', $time = '', $page = 1, $agent_id = 0){
    $map = [];
    $agent_user_agent_id = $agent_id ? $agent_id : $this->agent_id;
    if($keyword){
      $map['order_id|mobile'] = ['LIKE', "%{$keyword}%"];
    }
    if($time){
      $map['add_time'] = [['>', strtotime($time)], ['<', (strtotime($time) + 86400000)]];
    }
    if(Session::get('agent_admin')['is_employ_agent'] == 1){
  
      $where = "(parent_agent={$agent_user_agent_id} OR parent_agent=0 OR user_id={$agent_user_agent_id})";
    }else{
      $where = "(parent_agent={$agent_user_agent_id} OR user_id={$agent_user_agent_id})";
    }
    
    if($type && $type != ''){
      $where.=Config::get($type);
    }
    $list = $this->orderMdl->where($where)->where($map)->order('add_time DESC')->paginate(Config::get('pageSize'), false, ['page' => $page]);
    //dump($this->orderMdl->getLastSql());
    //dump($list);
    if($this->request->isAjax()){
      return $this->ajax('2000', '返回成功', '', $list);
    }else{
      return $this->fetch('waitpay-order', ['list' => $list]);
    }
  }
  /**
   * 订单详情
   * @param    [type]                   $order_id [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T10:57:15+080
   */
  public function detail($order_id = 0 ){
    if($order_id){
      $res = $this->orderMdl->find($order_id);
      $pay_status = [1=>'未支付',2=>'已支付',3=>'支付失败'];
      $shipping_status = [1=>'未发货',2=>'已发货',3=>'部分发货'];
      $button = $this->orderMdl->getOrderButton($res);
      //订单商品
      $orderGoods = Db::name('order_goods')->alias('og')->field(['og.*','g.thumb as goods_thum_images'])->join("__GOODS__ g", 'g.id=og.goods_id')->where("order_id",$order_id)->select();
      
      //操作信息
      $action_log = Db::name('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
      //查找用户昵称
      foreach ($action_log as $k => $v){
        if($v['action_user']){
          $userIds[$k] = $v['action_user'];
        }
      }
      // dump($action_log);
      if($userIds && count($userIds) > 0){
        $users = Db::name("user")->field(["id", "nickname"])->where("id in (".implode(",",$userIds).")")->select();
      }
      // dump(convert_arr_key($users, 'id'));
      return $this->ajax(2000, '获取成功', '', [
        'order'=>$res,
        'pay_status'=>$pay_status,
        'shipping_status' => $shipping_status,
        'orderGoods'  => $orderGoods,
        'button'=>$button,
        'action_log' => $action_log,
        'users'  => convert_arr_key($users, 'id'),
      ]);
    }else{
      $this->error('没有这样的订单');
    }
  }
  /**
   * 代理确认订单信息
   * @author: slide
   * @param $order_id
   */
  public function confirm_order($order_id){
    $updata = $this->orderMdl->find($order_id);
    $updata->order_status = 2;
    $res = $this->orderMdl->isUpdate(true)->save($updata->toArray());
    if($res) {
      logOrder($order_id,'confirm','代理确认订单成功', $this->agent_id);
      $this->success('操作成功');
    } else{
      logOrder($order_id,'confirm','代理确认订单失败', $this->agent_id);
      $this->error('操作失败');
    }
  }

  /**
   * 为订单付款
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-14T16:02:11+080
   */
  public function payForChildOrder($order_id, $user_id = ''){
    $agent_id = $this->agent_id;
    $order_info = $this->orderMdl->getOrderInfo($order_id);
    $user_id = $user_id ? $user_id : $this->agent_info['id'];
    //启动事务
    $params = ['order_id' => $order_id,'userId'=>$order_info['user_id']];
    Hook::call('Order', 'PayBeagin', $params);
    Db::startTrans();
    try{
      //改变订单状态
      $res = $this->orderMdl->orderProcessHandle($order_id, 'pay');
      //减少代理金币
      L('代理id'.$user_id);
      $ac_res = accountLog($user_id, $order_info['order_amount'], 2, '为下级支付订单');
      
      // 分成
      $order_goods = Db::name('order_goods')->alias('og')->field(['og.*','g.agent_config','g.store_count','title'])->join('__GOODS__ g', 'g.id=og.goods_id')->where('order_id', $order_id)->select();
      /*$order_goods_keys = implode(',', array_keys(convert_arr_key($order_goods, 'goods_id')));
      $goodsMdl = new Goods();
      $goods_agent_config = $goodsMdl->field(['id','agent_config', 'store_count','title'])->where("id IN ($order_goods_keys)")->select();*/
      $user_info = Db::name('user')->where('id',$order_info['user_id'])->find();
      
      //自己是代理往上3 否则 往上4
      $parent_agent_user = [];
      if($user_info['agent_type'] != 0){
        if($user_info['agent_type'] != 1){
          $parent_agent_user_arr = getUserFullPath($order_info['user_id'], 1, 4);  //上面三级用户
          array_push($parent_agent_user_arr, $user_info);
          $parent_agent_user = $parent_agent_user_arr;
        }
      }else{
        $parent_agent_user = getUserFullPath($order_info['user_id'], 1, 5);  //上面四级用户
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
        
         // 需要分成的总金额
        $total_money = $user_info['agent_type'] !=0 ? ( intval($temp_config[1]) * $v['goods_num'] - intval($temp_config[$user_info['agent_type']]) * $v['goods_num'] ) : intval($temp_config[1]) * $v['goods_num'];
        L('需要分成的总额'.$total_money);
        
        // 每个商品的利润
        $goods_profit = $v['profit'] * $v['goods_num'];
        
        $order_divided_total += $total_money;
        $divided_total = 0;
        foreach ($parent_agent_user as $user_key => $user_value){
          L('会员信息'.json_encode($user_value));
          if($user_value['agent_type'] == 0) continue; //不是代理直接下一轮分成
          if($divided_total >= $total_money) break;
          
          $have = ($total_money - $divided_total);
          
          if($user_key > 0){
            if(($parent_agent_user[$user_key - 1]['agent_type'] != 0) && ($parent_agent_user[$user_key - 1]['agent_type'] < $user_value['agent_type'])) continue;
            $curr_money = intval($temp_config[$user_value['agent_type']]) * $v['goods_num'] ;
            L('当前指针'.($user_key));
            L('上一个用户'.json_encode($parent_agent_user[($user_key - 1)], true));
            L('当前用户'.json_encode($user_value));
            $pre_money = $parent_agent_user[($user_key - 1)]['agent_type'] != 0 ? $temp_config[$parent_agent_user[($user_key - 1)]['agent_type']] * $v['goods_num'] : 0;
            
            // 用 代理的金额减去下一级已经分出去的金额 大于当前要分的，则分剩下的
            $temp_money = ($curr_money - $pre_money) < 0 ? ($curr_money - $pre_money) * -1 : ($curr_money - $pre_money);
            $temp_money = $temp_money > $have ? $have : $temp_money;
            accountLog($user_value['id'], $temp_money, 1, '下级购买商品分成');
            divideNote(['user_id'=> $user_value['id'], 'order_id'=>$order_info['order_id'],'commission'=>0, 'total_money' => $temp_money, 'level' => isset($user_value['level']) ? $user_value['level'] : 0,'type'=>1]);
            $divided_total += $temp_money;
            L('购物当前分成'.$temp_money, 'info');
            L('购物当前记录'.$curr_money, 'info');
            L('购物当前下一级记录'.$pre_money, 'info');
          }else{
            if($user_info['agent_type'] != 0 && ($user_value['agent_type'] > $user_info['agent_type'])) continue;
            // 直接上级，分满
            $temp_money = ($user_info['agent_type'] !=0 ) ? (intval($temp_config[$user_value['agent_type']]) * $v['goods_num'] - intval($temp_config[$user_info['agent_type']]) * $v['goods_num'] ) : intval($temp_config[$user_value['agent_type']]) * $v['goods_num'] ;
            accountLog($user_value['id'], $temp_money, 1, '下级购买商品分成');
            divideNote(['user_id'=> $user_value['id'], 'order_id'=>$order_info['order_id'],'commission'=>0, 'total_money' => $temp_money, 'level' => isset($user_value['level']) ? $user_value['level'] : 0,'type'=>1]);
            $divided_total += $temp_money;
            L('购物当前分成'.$temp_money, 'info');
          }
        }
        
        $order_divided_total += $divided_total;
        //所有人都分完，多余进公司账户
        // 自己是代理的话需要减掉自己的折扣
        $supplus_money = $user_info['agent_type'] !=0 ?  ($goods_profit - $divided_total - intval($temp_config[$user_info['agent_type']]) * $v['goods_num'] ) : $goods_profit - $divided_total;
        if($company_accout && $supplus_money){
          accountLog($company_accout['id'], $supplus_money, 1, '分成剩下的金额转入');
          divideNote(['user_id'=> $company_accout['id'], 'order_id'=>$order_info['order_id'],'commission'=>0, 'total_money' => $supplus_money, 'level' => 0, 'type'=>1]);
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
      $this->orderMdl->where('order_id', $order_id)->update(['is_distribut' => 1,'distribut_total' => $order_divided_total,'actual_distribut'=>$divided_total]);
      
      
      // 判断需不需要短信通知 && 模板消息通知
      $sms_add_order      = cache_data( 'site_config' )[ 'do_pay_success' ];
      $template_add_order = cache_data( 'site_config' )[ 'template_pay_success' ];
      // dump(Session::get($res));
      if ( $sms_add_order ) {
        // 短信通知
        $sms          = new Sms();
        $mobile       = Session::get( 'userInfo' )[ 'phone' ];
        $scren        = 'do_pay_success';
        $send_sms_res = $sms->sendSMS( $mobile, $scren, [ Config::get( 'SMS_SIGN' ), $order_info[ 'order_sn' ], '他人代付' ] );
        if ( $send_sms_res ) {
          Log::write( '支付短信:' . $order_info[ 'order_sn' ] . '短信发送成功', 'info' );
        } else {
          Log::write( '支付短信:' . $order_info[ 'order_sn' ] . '短信发送失败', 'info' );
        }
      }
  
      $wechat           = new Weichat();
      $weichat_code     = ( isset( $order_info[ 'weichat_sn' ] ) && $order_info[ 'weichat_sn' ] != '' ) ? '微信订单号' . $order_info[ 'weichat_sn' ] : '';
      if ( $template_add_order ) {
        // 模板消息通知
        
        $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/order/detail', [ 'order_id' => $order_id ] ), 'PAY_SUCCESS', [ "您在" . Config::get( 'SMS_SIGN' ) . "有一笔订单支付成功!", $order_info[ 'order_amount' ], '省级代理代付', "订单包含" . $goods_name . "等{$goods_total}件商品。", $order_info[ 'order_sn' ], $weichat_code, "我们将尽快给您安排发货，请及时注意物流信息。" ] );
        if ( $template_send_re ) {
          Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送成功', 'info' );
        } else {
          Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送失败', 'info' );
        }
      }
      // 发送消息模板消息给客服
      $kefu_uid = getUserInfoBywhere('phone', Config::get('agent_kefu_account'));
      $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
        '亲爱的发货专员商城有一笔新的订单成功支付,赶快去发货吧!','处理代理申请','待办','请尽快处理！'
      ], $kefu_uid['id']);
      if ( $template_send_kefu_res ) {
        Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送成功', 'info' );
      } else {
        Log::write( '订单:' . $order_info[ 'order_sn' ] . '模板消息发送失败', 'info' );
      }
  
      // 提交事务
      Db::commit();
  
      $params = ['order_id' => $order_info['order_id'], 'userId'=> $order_info['user_id']];
      Hook::call('Order', 'PaySuccess', $params);
      return $this->ajax(2000, '支付成功');
    } catch (\Exception $e) {
      // 回滚事务
      Db::rollback();
      L('错误'.$e);
      return $this->ajax(4000, '支付失败');
    }
  }

}
