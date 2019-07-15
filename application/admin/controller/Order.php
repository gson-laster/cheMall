<?php
namespace app\admin\controller;

use think\Config;
use think\Db;
use think\Session;
use think\Log;

use app\api\controller\Sms;
use app\home\controller\Weichat;
use app\common\model\Order as OrderModel;
use app\common\model\User as UserModel;
/**
 * 后台订单管理
 */
class Order extends AdminBase
{
  protected $orderMdl;
  public  $order_status;
  public  $pay_status;
  public  $shipping_status;
  protected function _initialize(){
    parent::_initialize();
    $this->orderMdl = new OrderModel();
    $this->order_status = Config::get('ORDER_STATUS');
    $this->pay_status = Config::get('PAY_STATUS');
    $this->shipping_status = Config::get('SHIPPING_STATUS');
    // 订单 支付 发货状态
    $this->assign('order_status',$this->order_status);
    $this->assign('pay_status',$this->pay_status);
    $this->assign('shipping_status',$this->shipping_status);
  }

  /**
   * 订单列表
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T09:24:37+080
   */
  public function index ($order_type= '', $type_time='add_time', $order_sn = '', $start = 0, $end = 0, $page=1) {
    //查询条件
    $map = [];
    if($start){
      $map[$type_time]=strtotime($start);
    }

    if($end){
      $map[$type_time]=strtotime($end);
    }

    if($start && $end){
      $map[$type_time] = ['between', [strtotime($start), strtotime($end)]];
    }

    if($order_sn){
      $map['o.order_sn'] = $order_sn;
    }

    $where = "1=1";
    if($order_type){
      $where .= Config::get($order_type);
    }
    //dump($where);

    $order_list = $this->orderMdl->alias('o')->field([
      'o.order_id','o.user_id','o.order_sn','o.order_amount','o.total_amount','o.order_status','o.shipping_status','o.pay_status','o.shipping_name','o.shipping_code','o.shipping_price','o.pay_name', 'o.add_time','o.consignee','o.mobile','ag.is_service', 'o.province','o.city','o.district','o.twon','o.address'
    ])
    ->join('__AGENT_EMPLOYEE__ ag', 'ag.agent_id=o.user_id','LEFT')
    ->where($map)->where($where)->order("order_id desc")
    ->paginate(Config::get('pageSize'), false, ['page' => $page,"query"=>['order_type'=>$order_type, 'order_sn'=>$order_sn,'start'=>$start,'end'=>$end]]);
   // dump($order_list);
    return $this->fetch('index', [
      'order_list'  =>  $order_list
    ]);
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
      return $this->fetch('detail', [
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
   * 订单操作
   * @param $id
   */
  public function order_action($type = '', $order_id = '', $note = ''){
      $action = $type;
      $order_id = $order_id;

      if($action && $order_id){
          $res = true;
          if($action !=='pay'){
              $res = $this->orderMdl->orderActionLog($order_id,$action,input('note'));
          }
          $admin = Session::get('admin_id') ? Session::get('admin_id') : 0;
         $a = $this->orderMdl->orderProcessHandle($order_id,$action,array('note'=>$note,'admin_id'=>$admin));
        //  dump($a);
         //dump($res);exit;
         if($res !== false && $a !== false){
               if ($action == 'remove') {
                   exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => url('admin/order/index')))));
               }
          exit(json_encode(array('status' => 1,'msg' => '操作成功')));
         }else{
               if ($action == 'remove') {
                   exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => url('admin/order/index')))));
               }
          exit(json_encode(array('status' => 0,'msg' => '操作失败')));
         }
      }else{
        $this->error('参数错误',url('Admin/Order/detail',array('order_id'=>$order_id)));
      }
  }

  /**
   * 订单取消付款
   */
  public function pay_cancel($order_id){
    if(input('remark')){
      $data = input('post.');
      $note = array('退款到用户余额','已通过其他方式退款','不处理，误操作项');
      if($data['refundType'] == 0 && $data['amount']>0){
        accountLog($data['user_id'], $data['amount'], 1,  '退款到用户余额');
      }
      $a = $this->orderMdl->orderProcessHandle($data['order_id'],'pay_cancel');
      // dump($a);exit;
      $d = $this->orderMdl->orderActionLog($data['order_id'],'pay_cancel',$data['remark'].':'.$note[$data['refundType']]);
      if($a && $d){
        $this->success('退款成功', url('index'));
      }else{
        $this->success('退款失败', url('pay_cancel',['order_id'=>$order_id]));
      }
    }else{
      $order = $this->orderMdl->find($order_id);
      $this->assign('order',$order);
      return $this->fetch();
    }
  }

  /**
   * 生成发货单
   */
  public function deliveryHandle(){
      $data = input('post.');
      $res = $this->orderMdl->deliveryHandle($data);
      if($res){
        //判断需不需要短信通知 && 模板消息通知
        $sms_add_order = cache_data('site_config')['do_send_goods_success'];
        $template_add_order = cache_data('site_config')['template_send_goods'];
        $order_info = $this->orderMdl->getOrderInfo($data['order_id']);
        if($sms_add_order){
          //短信通知
          $sms = new Sms();
          $mobile = Db::name('user')->where('id', $order_info['user_id'])->value('phone');
          $scren = 'do_send_goods_success';
          $send_sms_res = $sms->sendSMS($mobile, $scren, [Config::get('SMS_SIGN'), $order_info['order_sn'], $res['consignee'], $res['invoice_no']]);
          if($send_sms_res){
            Log::write('支付短信:'.$order_info['order_sn'].'短信发送成功','info');
          }else{
            Log::write('支付短信:'.$order_info['order_sn'].'短信发送失败','info');
          }
        }

        if($template_add_order){
          //模板消息通知
          $wechat = new Weichat();
          $template_send_re = $wechat->sendTemplateMsg(WEB_DOMAIN.url('home/order/detail',['order_id'=>$data['order_id']]), 'SEND_GOODS', ["您在".Config::get('SMS_SIGN')."有一笔订单已经发货!", $order_info['order_sn'], $order_info['shipping_name'], [$res['invoice_no']], date("Y年m月d日 H:i"), "请注意物流信息,及时查收，祝您生活愉快！"], $order_info['user_id']);
          if($template_send_re){
            Log::write('订单:'.$order_info['order_sn'].'模板消息发送成功','info');
          }else{
            Log::write('订单:'.$order_info['order_sn'].'模板消息发送失败','info');
          }
        }
        $this->success('操作成功',url('Admin/Order/index',array('order_id'=>$data['order_id'])));
      }else{
        $this->success('操作失败',url('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
      }
  }

  /**
   * 发货单
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T15:36:25+080
   */
  public function delivery_info(){
    $order_id = input('order_id');
    $order = $this->orderMdl->getOrderInfo($order_id);
    $orderGoods = $this->orderMdl->getOrderGoods($order_id);
    $delivery_record = Db::name('delivery_doc')->alias('d')->join('__ADMIN__ a','a.id = d.admin_id')->where('d.order_id='.$order_id)->select();
    if($delivery_record){
      $order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
    }
    //操作信息
    $action_log = Db::name('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
    //查找用户昵称
    $userIds = [];
    foreach ($action_log as $k => $v){
        if($v['action_user']){
          $userIds[$k] = $v['action_user'];
        }
    }
    // dump($action_log);
    if($userIds && count($userIds) > 0){
        $users = Db::name("user")->field(["id", "nickname"])->where("id in (".implode(",",$userIds).")")->select();
       $this->assign('users' , convert_arr_key($users, 'id'));
    }

    $this->assign('action_log' , $action_log);

    $this->assign('order',$order);
    $this->assign('orderGoods',$orderGoods);
    $this->assign('delivery_record',$delivery_record);//发货记录
    $this->assign('shipping_config', Config::get('shipping_config'));
    return $this->fetch();
  }

  /**
   * 发货单列表
   */
  public function delivery_list(){
      return $this->fetch();
  }

  /*
   * 价钱修改
   */
  public function editprice($order_id){
      $order = $this->orderMdl->getOrderInfo($order_id);
      $this->editable($order);
      if($this->request->isPost()){
        $admin_id = Session::get('admin_id');
          if(empty($admin_id)){
              $this->error('非法操作');
              exit;
          }
          $update['discount'] = input('post.discount');
          $update['shipping_price'] = input('post.shipping_price');
          $update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'];
          // dump($update);exit;
          $row = $this->orderMdl->where(array('order_id'=>$order_id))->update($update);
          if(!$row){
              $this->success('没有更新数据',url('Admin/Order/editprice',array('order_id'=>$order_id)));
          }else{
              $this->success('操作成功',url('Admin/Order/detail',array('order_id'=>$order_id)));
          }
          exit;
      }
      // dump($order);
      $this->assign('order',$order);
      return $this->fetch();
  }

  /**
   * 检测订单是否可以编辑
   * @param $order
   */
  private function editable($order){
      if($order['shipping_status'] != 1){
          $this->error('已发货订单不允许编辑');
          exit;
      }
      return;
  }

  /**
   * 删除记录
   * @param    integer                  $id  [description]
   * @param    [type]                   $ids [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-18T10:56:52+080
   */
  public function deleteOrder($id = 0, $ids = []){
    $id = $id ? $id : $ids;
    if($id){
      $this->request->get();
      if($this->orderMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的记录');
    }
  }


  /**
   * 退款列表
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-24T14:55:59+080
   */
  public function refund($page=1){
    if($this->request->isPost()){
      if(input('order_id')){
        $order_id = input('order_id');
        $res = $this->orderMdl->where("order_status=4")->find($order_id);
        if($res){
          Db::startTrans();
          try{

            //更改用户余额
            // $user_res = Db::name('user')->where("id",$res['user_id'])->setInc('user_vb', $res['order_amount']);

            accountLog($res['user_id'], $res['order_amount'], 1, '商品退款');
            //更改商品信息
            // 增加对应商品的库存
            $orderGoodsArr = Db::name('OrderGoods')->where("order_id", $order_id)->select();
            // dump(1);?
            $goodsDb = Db::name('Goods');
            foreach($orderGoodsArr as $key => $val)
            {
              log::write('商品信息'.json_encode($val),'info');
              $change_res = changeStore($val['goods_id'], $val['goods_num']*(-1), $val['spec_key']);  //增加商品库存
              $buy_res = $goodsDb->where("id={$val['goods_id']}")->setDec('buy_num',$val['goods_num']); // 减少商品销售量
              // dump($buy_res);exit;

            }
            Log::write('商品更改成功', 'WARN');
            //更改订单
            $data['order_status'] = 6;
            // $this->orderMdl->data($data, true);
            Log::write('订单'.json_encode($data), 'WARN');

            $order_res = Db::name('order')->where('order_id', $order_id)->update($data);

            Log::write('订单更改成功', 'WARN');
            Db::commit();
            return $this->ajax(1002, '退款成功');
          } catch (\Exception $e) {
              // 回滚事务
              Db::rollback();
              log::write('cuowu '.$e);
              return $this->ajax(1001, '退款失败');
          }

        }else{
          $this->error('本条订单不需要退款');
        }
      }else{
        $this->error('没有这样的订单');
      }
    }
    $list = $this->orderMdl->where("order_status=7 or order_status=4")->paginate(Config::get('pageSize'), false, ['page'=>$page]);
    // dump($list);
    return $this->fetch('order_return', [
      'list'  => $list
    ]);
  }

  /**
   * 订单分成
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-25T09:37:58+080
   */
  public function divideOrder($order_id='', $type=''){
    if($this->request->isPost()){
      if($order_id && $type){
        if($type==1){
          //订单信息
          $order_res = $this->orderMdl->where("order_status=3 OR order_status=5")->find($order_id);
          if(!$order_res) $this->error('此订单不需要分成');
          //用户上下级信息
          $user = new UserModel();
          $user_sup = $user->getSup($order_res['user_id']);

          //获取分成配置
          $goods_res = Db::name('order_goods')->alias('og')->field(['og.rec_id','og.goods_total','g.commission1','g.commission2','g.commission3'])->join("__GOODS__ g", 'g.id=og.goods_id')->where("order_id", $order_id)->select();
          // dump($goods_res);
          Db::startTrans();
          try{
            // $commission1_vb = 0;
            // $commission2_vb = 0;
            // $commission3_vb = 0;
            // $rec_ids = '';
            $data = [];
            foreach ($goods_res as $k => $v) {
              $commission1_vb = $v['goods_total'] * ($v['commission1'] / 100);
              $commission2_vb = $v['goods_total'] * ($v['commission2'] / 100);
              $commission3_vb = $v['goods_total'] * ($v['commission3'] / 100);

              //更改vb 并且 记录变动情况
              // 上上级
              if($user_sup['p1']){
                accountLog($user_sup['p1'],$commission3_vb, 1, '商品分成收入');
                divideNote([
                  'user_id' => $user_sup['p1'],
                  'order_id'=> $order_res['order_id'],
                  'rec_id'=> $v['rec_id'],
                  'commission' => ($v['commission3'] / 100),
                  'total_money'=> $v['goods_total'] * ($v['commission3'] / 100),
                  'level' => 1
                ]);
              }

              // 上上级
              if($user_sup['p2']){
                accountLog($user_sup['p2'],$commission2_vb, 1, '商品分成收入');
                divideNote([
                  'user_id' => $user_sup['p2'],
                  'order_id'=> $order_res['order_id'],
                  'rec_id'=> $v['rec_id'],
                  'commission' => ($v['commission2'] / 100),
                  'total_money'=> $v['goods_total'] * ($v['commission2'] / 100),
                  'level' => 2
                ]);
              }

              // 上级
              if($user_sup['p3']){
                accountLog($user_sup['p3'],$commission1_vb, 1, '商品分成收入');
                divideNote([
                  'user_id' => $user_sup['p3'],
                  'order_id'=> $order_res['order_id'],
                  'rec_id'=> $v['rec_id'],
                  'commission' => ($v['commission1'] / 100),
                  'total_money'=> $v['goods_total'] * ($v['commission1'] / 100),
                  'level' => 3
                ]);
              }
            }
            $this->orderMdl->where("order_id", $order_id)->update(['order_status'=>7,'is_distribut'=>1]);
            Db::commit();

            return $this->ajax(1002, '分成成功');
          } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            Log::write("错误".$e, 'ERROE');
            return $this->ajax(1001, '分成失败');
          }
        }else{

          if($this->orderMdl->where("order_id", $order_id)->setField('order_status',8)){
            $this->success('修改状态成功');
          }else{
            $this->error('修改状态失败');
          }

        }
      }else{
        $this->error('缺少必要参数');
      }
    }else{
      //查已经完成的订单
      $res = $this->orderMdl->where("order_status IN (3,5,7,8)")->order("order_id DESC")->select();
      // dump($this->orderMdl->getLastSql());
      return $this->fetch('divideOrder', [
        'list'    => $res
      ]);
    }
  }
}
