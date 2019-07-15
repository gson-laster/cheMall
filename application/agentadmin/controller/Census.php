<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/20 0020
 * Time: 下午 4:40
 */

namespace app\agentadmin\controller;

use think\Config;
use think\Session;
use think\Db;

use app\common\model\Order;
use app\common\model\User;

class Census extends Common {
  protected $orderMdl;
  protected $agent_id;
  protected $agent_info;
  protected function _initialize(){
    parent::_initialize();
    if($this->isAgentAdmin()){
      $this->agent_id = Session::get('agent_admin')['id'];
    }else{
      $this->agent_id = Session::get('agent_employee')['agent_id'];
    }
    $this->agent_info = Db::name('user')->find($this->agent_id);
  }
  
  /**
   * @author: slide
   * @return mixed
   *
   */
   public function index(){
    return $this->fetch();
   }


  /**
   * 会员统计
   * @author: slide
   * @param string $start_time
   * @param string $end_time
   *
   * @return \think\response\Json
   *
   */
  public function UserCount($start_time='', $end_time=''){
    $map = [];
    $order_wh = [];
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
      $order_wh['add_time|pay_time'] = ['between', [strtotime($start_time), time()]];
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
      $order_wh['add_time|pay_time'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $order_wh['add_time|pay_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
    }
    $map['parent_agent'] = $this->agent_id;
    $user = new User();
    //普通会员
    $user_list = $user->where($map)->select();
    $user_count = count($user_list);
    $user_list_key = convert_arr_key($user_list, 'id');
    
    //代理信息
    $agent_info = Db::name('agent')->select();
    $agent_info_key = convert_arr_key($agent_info, 'id');
    
    $user_ids = implode(',', array_keys($user_list_key));
    
    $agent_arr = [];
    $total_child_free = 0;
    $order_num = 0;
    $total_order_mount = 0;
    $myself_amont = 0;
    $child_amount = 0;

    // 代理申请并通过
    if($user_ids != ''){
      unset($map['parent_agent']);
      $agent_apply = Db::name('agent_apply')->where($map)->where("user_id IN ($user_ids) AND status=1")->select();
      //下级代理申请人数
      $agent_arr = [
        'first' => 0,
        'second' => 0,
        'third' => 0,
        'forth' => 0
      ];
      // 下级代理申请金额
      $total_child_free = 0;
      foreach ($agent_apply as $k => $v){
        if($v['agent_type'] == 1){
          $agent_arr['first'] ++;
          
        }
      
        if($v['agent_type'] == 2){
          $agent_arr['second'] ++;
        }
      
        if($v['agent_type'] == 3){
          $agent_arr['third'] ++;
        }
      
        if($v['agent_type'] == 4){
          $agent_arr['forth'] ++;
        }
        $total_child_free += $agent_info_key[$v['agent_type']]['free'];
      }
    
      //下级订单数
      $orderMdl = new Order();
      $order_res = $orderMdl->where("user_id IN ($user_ids)")->where($order_wh)->select();
      $order_num = count($order_res);
      //下级交易额
      $total_order_mount = 0;
      foreach ($order_res as $v => $k){
        $total_order_mount += $k['order_amount'];
      }
      //我的分成收入
      $user_ids .= ','.$this->agent_id;
      $divide_res = Db::name('divide_note')->where("user_id IN ($user_ids)")->where($map)->select();
      //我的
      $myself_amont = 0;
      
      //下级的
      $child_amount = 0;
      foreach ($divide_res as $key => $value){
        if($value['user_id'] == $this->agent_id){
          $myself_amont += $value['total_money'];
        }else{
          $child_amount += $value['total_money'];
        }
      }
    }
    
    
    return json([
      'user_count'=>$user_count, //下级推广会员
      'agent_acc' => $agent_arr, // 下级代理
      'agent_free'=> $total_child_free, // 下级代理申请费用
      'order_num' => $order_num, //下级订单
      'child_order_amount'  => $total_order_mount, //下级交易额
      'myself_amount'  => $myself_amont, //我的分成
      'child_amount'   => $child_amount, //下级分成
    ]);
  }
  
  /**
   * 下级树
   * @author: slide
   */
  public function childTree($pid = ''){
    $parent_id = $pid ? $pid : $this->agent_id;
    $user_list = Db::name('user')->where('pid', $parent_id)->select();
    foreach ($user_list as $k => &$v){
      $temp_user = Db::name('user')->where('pid', $v['id'])->count();
      if($temp_user){
        $v['hasChild'] = true;
        $v['childCount'] = $temp_user;
      }else{
        $v['hasChild'] = false;
        $v['childCount'] = 0;
      }
    }
    if($this->request->isAjax()){
      return $this->ajax(2000, '查询成功', '', $user_list);
    }else{
      $this->assign('user_list', $user_list);
      return $this->fetch();
    }
  }
  
  /**
   * 下级销售明细
   * @author: slide
   *
   * @param string $keyword   订单号
   * @param int    $page
   *
   */
  public function child_goods_list($keyword = '', $page = 1){
    $map = [];
    if($keyword){
      $map['order_sn'] = ['LIKE', "%{$keyword}%"];
    }
    $orderMdl = new Order();
    $where['parent_agent'] = $this->agent_id;
    $user = new User();
    //普通会员
    $user_list = $user->where($where)->select();
    $user_list_key = convert_arr_key($user_list, 'id');
    $user_ids = implode(',', array_keys($user_list_key));
    $goods_list = [];
    if($user_ids != ''){
      $order_res = $orderMdl->field(['order_id','order_sn','user_id'])->where($map)->where("user_id IN ($user_ids)")->select();
  
      $order_ids = implode(',',array_keys(convert_arr_key($order_res, 'order_id')));
      if($order_ids != ''){
        $goods_list = Db::name('order_goods')->where("order_id IN ($order_ids)")->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      }
    }
    
    $this->assign('goods_list', $goods_list);
    return $this->fetch();
  }
}
