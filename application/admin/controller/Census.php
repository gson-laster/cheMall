<?php
namespace app\admin\controller;

use think\Config;
use think\Db;

use app\common\model\Order;
use app\common\model\User;
/**
 * 统计
 */
class Census extends AdminBase
{
  protected function _initialize(){
    parent::_initialize();
  }
  /**
   * 订单统计
   * @param    [type]                   $type_time  [add_time下单，shipping_time发货，confirm_time收货确认，pay_time支付时间]
   * @param    string                   $start_time [开始时间]
   * @param    string                   $end_time   [结束时间]
   * @Author:  slade
   * @DateTime :2017-05-02T11:23:45+080
   */
  public function Order($type_time,$start_time='', $end_time=''){
    $map = [];
    if($start_time){
      $map[$type_time]=strtotime($start_time);
    }

    if($end_time){
      $map[$type_time]=['between',[strtotime($end_time),strtotime($end_time)+86400000]];
    }

    if($start_time && $end_time){
      $map[$type_time] = ['between', [strtotime($start_time), strtotime($end_time)]];
    }
    $orderMdl = new Order();

    $order_res = $orderMdl->where($map)->select();

    return $this->fetch();
  }

  public function index(){

    return $this->fetch();
  }

  /**
   * 用户推广统计
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-02T11:05:37+080
   */
  public function userCount($start_time='', $end_time=''){
    $map = [];
    $time_string="";
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400000);
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400000);
    }
    $user = new User();
    $user_list = $user->where($map)->count();

    // 代理申请并通过
    $agent_apply = Db::name('agent_apply')->where($map)->where('status=1')->select();
    $agent_arr = [
      'first' => 0,
      'second' => 0,
      'third' => 0,
      'forth' => 0,
      'first_total' => 0,
      'second_total' => 0,
      'third_total' => 0,
      'forth_total' => 0,
    ];
//    dump(Db::name('agent_apply')->getLastSql());
    $total_agent_money = 0;
    foreach ($agent_apply as $k => $v){
      if($v['agent_type'] == 1){
        $agent_arr['first'] ++;
        $agent_arr['first_total'] += $v['money'];
      }

      if($v['agent_type'] == 2){
        $agent_arr['second'] ++;
        $agent_arr['second_total'] += $v['money'];
      }

      /*if($v['agent_type'] == 3){
        $agent_arr['third'] ++;
        $agent_arr['third_total'] += $v['money'];
      }

      if($v['agent_type'] == 4){
        $agent_arr['forth'] ++;
        $agent_arr['forth_total'] += $v['money'];
      }*/

      $total_agent_money += $v['money'];
    }

    // 查出公司账户
    $company_account = Db::name('user')->where('is_employ_agent', 1)->find();
    $company_account_id = $company_account ? $company_account['id'] : 0;

    // 分成
    $map['type'] = 2;
    $total_agent_divide = Db::name('divide_note')->where($map)->select();

    $divide_count = 0;
    $divide_price = 0;
    $company_account_price = 0;
    foreach ($total_agent_divide as $k=>$v){
      // 排除 公司账户
      if($v['user_id'] == $company_account_id){
        $company_account_price += $v['total_money'];
      }else{
        $divide_count ++;
        $divide_price += $v['total_money'];
      }
    }
    return json([
      'user_count'=> $user_list, //新增会员
      'agent_acc' => $agent_arr, // 代理数据【新增和代理费用】
      'timestring'=> $time_string,
      'total_money'=> $total_agent_money, // 新增代理总费用
      'agent_company_account_price' => $company_account_price, // 公司账户结余
      'agent_divide_price' => $divide_price, // 分成额
      'agent_divide_count' => $divide_count // 分成笔数
    ]);
  }

  /**
   * 商品统计
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-02T11:12:34+080
   */
  public function goodsCount($start_time='', $end_time=''){
    $map = [];
    $order = [];
    $time_string = "";
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
      $order['pay_time'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $order['pay_time'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $order['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      //dump(1);
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
    }

    // 申请充值成功的
    $recharge = DB::name('user_recharge')->field(['money'])->where($map)->where("status", 2)->select();
    $recharge_num = 0;
    $recharge_price = 0;

    foreach ($recharge as $k=>$v){
      $recharge_num ++;
      $recharge_price += $v['money'];
    }

    // 体现
    $tx_res = Db::name('usersqtx')->field(['actual'])->where($map)->where('isok',1)->select();
    $tx_num = 0;
    $tx_price = 0;
    foreach ($tx_res as $k => $v){
      $tx_num ++;
      $tx_price += $v['actual'];
    }

    // 省代公司利润分红
    $agent_company_divide = Db::name('agent_company_divide')->where($map)->select();
    $agent_company_divide_num = 0;
    $agent_company_divide_price = 0;

    foreach ($agent_company_divide as $v => $k){
      $agent_company_divide_num ++;
      $agent_company_divide_price += $v['money'];
    }

    $order_res = Db::name('order')->where("order_status IN (2,3,5,7) AND pay_status=2 AND is_distribut=1")->where($order)->select();
    //dump(Db::name('order')->getLastSql());exit;
    //dump($order_res);
    $order_ids ='';
    $order_count_price = 0;
    $goods_real_amount = 0;
    foreach ($order_res as $k => $v){
      $order_ids .= $v['order_id'].',';
      $goods_real_amount += $v['order_amount'];
      $order_count_price += $v['total_amount'];
    }
    //dump($goods_real_amount);
    //dump($order_count_price);
    $order_ids = trim($order_ids, ',');
    //dump($goods_real_amount);exit;
    $order_count_number = 0;
    $profit = 0; //利润
    // 商品利润
//    dump($order_ids);
    $order_goods = [];
    if($order_ids != ''){
      unset($map['createtime']);
      $map['og.order_id'] = ["exp", "IN (".$order_ids.")"];
      $order_goods = Db::name('order_goods')->alias('og')->field(['og.profit','og.goods_name','og.goods_num','og.goods_total','og.goods_price', 'o.is_distribut','o.order_sn','o.order_id'])->join('__ORDER__ o','o.order_id=og.order_id','LEFT')->where($map)->select();
      //dump(Db::name('order_goods')->getLastSql());
     // dump($order_goods);
      foreach ($order_goods as $key => $value){
        $profit += $value['profit'] * $value['goods_num'];
        $order_count_number += $value['goods_num'];
        //$order_count_price += $value['goods_total'];
      }
      unset($map['og.order_id']);
      $map['order_id'] = ["exp", "IN (".$order_ids.")"];
    }

    // 查出公司账户
    $company_account = Db::name('user')->where('is_employ_agent', 1)->find();
    $company_account_id = $company_account ? $company_account['id'] : 0;

    // 购买商品分成 type = 1
    $map['type'] = 1;
    // 排除 公司账户
    //$map['user_id'] = ['<>', $company_account_id];

    $divide_res = Db::name('divide_note')->where($map)->select();
//    $divide_count = count($divide_res);
    $divide_count = 0;
    $divide_price = 0;
    $company_account_price = 0;
    foreach ($divide_res as $k=>$v){
      // 排除 公司账户
      if($v['user_id'] == $company_account_id){
        $company_account_price += $v['total_money'];
      }else{
        $divide_count ++;
        $divide_price += $v['total_money'];
      }
    }
    //dump($profit);
    // 最终利润
    $profit = $profit - ($order_count_price - $goods_real_amount);

    return json([
      'recharge_num' => $recharge_num, // 充值笔数
      'recharge_price' => $recharge_price, // 充值金额
      'number'=>$order_count_number, // 销售数量
      'price'=>$order_count_price, // 销售金额
      'real_price' => $goods_real_amount, // 折扣价之后的金额
      'tx_price' => $tx_price, // 提现金额
      'tx_num' => $tx_num, // 提现笔数
      'goods_profit' => $profit, // 毛利
      'profit'=> ($profit - $divide_price), //产生的利润
      'devide_count'=>$divide_count, //分成几笔
      'devide_price'=>$divide_price, //分成多少钱
      'company_account_parice' => $company_account_price, // 公司账户
      'agent_company_divide_num' => $agent_company_divide_num, // 省代分红笔数
      'agent_company_divide_price' => $agent_company_divide_price, // 省代分红金额
      'time_string' => $time_string,
    ]);
  }

  /**
   * 总价统计及页面指定
   * @author: slide
   */
  public function totalCount(){
    // 申请充值成功的
    $recharge = DB::name('apply_recharge')->field(['money'])->where("status", 1)->select();
    $recharge_num = 0;
    $recharge_price = 0;

    foreach ($recharge as $k=>$v){
      $recharge_num ++;
      $recharge_price += $v['money'];
    }

    // 体现
    /*$tx_res = Db::name('usersqtx')->field(['actual'])->where('isok',1)->select();
    $tx_num = 0;
    $tx_price = 0;
    foreach ($tx_res as $k => $v){
      $tx_num ++;
      $tx_price += $v['actual'];
    }*/

    // 省代公司利润分红
    /*$agent_company_divide = Db::name('agent_company_divide')->select();


    foreach ($agent_company_divide as $v => $k){
      $agent_company_divide_num ++;
      $agent_company_divide_price += $v['money'];
    }*/

    $profit = 0; //利润

    // 代理申请并通过
    $agent_apply = Db::name('agent_apply')->where('status=1')->select();
    $total_agent_money = 0;
    $total_agent_num = 0;
    foreach ($agent_apply as $k => $v){
      $total_agent_money += $v['money'];
      $profit += $v['money'];
      $total_agent_num ++;
    }

    $order_res = Db::name('order')->where("order_status IN (2,3,5,7) AND pay_status=2 AND is_distribut=1")->select();
    //dump(Db::name('order')->getLastSql());exit;
    //dump($order_res);
    $order_ids ='';
    $order_count_price = 0;
    $goods_real_amount = 0;
    foreach ($order_res as $k => $v){
      $order_ids .= $v['order_id'].',';
      $goods_real_amount += $v['order_amount'];
      $order_count_price += $v['total_amount'];
    }
    $order_ids = trim($order_ids, ',');
    //dump($goods_real_amount);exit;
    $order_count_number = 0;
    // 商品利润
    $order_goods = [];
    if($order_ids != ''){
      $map['og.order_id'] = ["exp", "IN (".$order_ids.")"];
      $order_goods = Db::name('order_goods')->alias('og')->field(['og.profit','og.goods_name','og.goods_num','og.goods_total','og.goods_price', 'o.is_distribut','o.order_sn','o.order_id'])->join('__ORDER__ o','o.order_id=og.order_id','LEFT')->select();
      foreach ($order_goods as $key => $value){
        $profit += $value['profit'] * $value['goods_num'];
        $order_count_number += $value['goods_num'];
      }
    }

    // 查出公司账户
    $company_account = Db::name('user')->where('is_employ_agent', 1)->find();
    $company_account_id = $company_account ? $company_account['id'] : 0;

    // 排除 公司账户
    $divide_res = Db::name('divide_note')->select();

    $divide_count = 0;
    $divide_price = 0;
    $goods_count = 0;
    $goods_price = 0;
    $company_account_price = 0;
    $agent_company_divide_num = 0; // 分红补贴
    $agent_company_divide_price = 0;
    foreach ($divide_res as $k=>$v){
      // 排除 公司账户
      if($v['user_id'] == $company_account_id){
        $company_account_price += $v['total_money'];
      }else{
        if ($v['type'] == 1){
          $goods_count ++;
          $goods_price += $v['total_money'];
        }else if($v['type'] == 2){
          $divide_count ++;
          $divide_price += $v['total_money'];
        }else if($v['type'] == 3){
          $agent_company_divide_num ++;
          $agent_company_divide_price+= $v['total_money'];
        }else if($v['type'] == 4) {
          // 团队奖励
        }else{
          // 领导人津贴
        }
      }
    }
    //dump($agent_company_divide_price);

    // 会员
    $user_res = model('user')->field(['user_vb','id'])->select();

    $user_num = 0;
    $user_price =0;
    foreach ($user_res as $k=>$v){
      if($v['id'] != $company_account_id){
        $user_num ++;
        $user_price += $v['user_vb'];
      }
    }
    // 利润 = 总利润 - 代理分成 - 商品分成 - 商品折扣
    $last_profit = $profit - $divide_price - $goods_price - ($order_count_price - $goods_real_amount);

    return $this->fetch('total_count', [
      'recharge_num' => $recharge_num, // 充值数
      'recharge_price' => $recharge_price, // 充值金额
      'order_count_number' => $order_count_number, //销量
      'goods_real_amount' => $order_count_price, // 销售额
      'goods_count' => $goods_count, // 商品分成笔数
      'goods_price' => $goods_price, // 商品分成金额
      'divide_count' => $divide_count, // 代理分成笔数
      'divide_price' => $divide_price, // 代理分成金额
      'agent_company_divide_num' => $agent_company_divide_num, // 分红补贴笔数
      'agent_company_divide_price' => $agent_company_divide_price, // 分红补贴金额
      'user_num' => $user_num, // 会员总数
      'user_price' => $user_price, // 会员结余
      'last_profit' => $profit, // 利润
      'company_last' => $company_account_price // 公司结余
    ]);

  }

  /**
   * 销售明细
   * @param    string                   $start_time [description]
   * @param    string                   $end_time   [description]
   * @Author:  slade
   * @DateTime :2017-05-02T12:01:20+080
   */
  public function Saledetails($start_time='',$end_time='', $page=1){
    $map= [];
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
    }
    // ID 商品名称 货号 规格 数量 单价 总价 销售时间
    $goods_list = Db::name('order_goods')->alias('og')->field(['og.rec_id','og.goods_name','og.order_id','og.goods_sn','og.spec_key_name','og.goods_num','og.goods_price','og.goods_total','og.createtime','o.order_sn','o.pay_status','o.order_status'])->join('__ORDER__ o','o.order_id=og.order_id','LEFT')->where($map)->order('createtime DESC')->paginate(Config::get('pageSize'),false, ['page'=>$page]);
    //dump($goods_list);
    return $this->fetch('sale_details',['list'=>$goods_list,'start_time'=>$start_time,'endtime'=>$end_time]);
  }

  /**
   * 商品订单统计
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-02T15:07:26+080
   */
  public function orderCount(){
    $orderMdl = new Order();
    //交易金额
    $sale_total_succ = $orderMdl->where("order_status IN (3,5,7)")->sum('order_amount');
    //订单数量(数量)
    $order_count = $orderMdl->count();
    //交易成功(数量)
    $order_succ = $orderMdl->where("order_status IN (3,5,7)")->count();
    //交易失败(数量)
    $order_fail = $orderMdl->where("order_status IN (4,6)")->count();
    //退款金额(元)
    $sale_order_fail = $orderMdl->where("order_status IN (4,6)")->sum('order_amount');

    //月数据（订单数，销售额，退款额）
    $feb_day = date('Y') % 4 == 0 ? 29 : 28;
    $month = [
      'Jan' => [strtotime(date('Y')."-01-01"),strtotime(date('Y')."-01-31")],
      'Feb' => [strtotime(date('Y')."-02-01"),strtotime(date('Y')."-02-{$feb_day}")],
      'Mar' => [strtotime(date('Y')."-03-01"),strtotime(date('Y')."-03-31")],
      'Apr' => [strtotime(date('Y')."-04-01"),strtotime(date('Y')."-04-30")],
      'May' => [strtotime(date('Y')."-05-01"),strtotime(date('Y')."-05-31")],
      'Jun' => [strtotime(date('Y')."-06-01"),strtotime(date('Y')."-06-30")],
      'Jul' => [strtotime(date('Y')."-07-01"),strtotime(date('Y')."-07-31")],
      'Aug' => [strtotime(date('Y')."-08-01"),strtotime(date('Y')."-08-31")],
      'Sep' => [strtotime(date('Y')."-09-01"),strtotime(date('Y')."-09-30")],
      'Oct' => [strtotime(date('Y')."-10-01"),strtotime(date('Y')."-10-31")],
      'Nov' => [strtotime(date('Y')."-11-01"),strtotime(date('Y')."-11-30")],
      'Dec' => [strtotime(date('Y')."-12-01"),strtotime(date('Y')."-12-31")],
    ];

    //查出本年的订单
    $year_order_res = $orderMdl->field(['add_time','order_amount','order_status'])->where(['add_time'=>['between', [$month['Jan'][0],strtotime(date('Y')."-12-31 23:59:59")]]])->select();
    $price_data = [];
    $order_data = [];
    $refen_data = [];
      foreach ($month as $k => $v) {
        $temp_price = 0;
        $temp_order = 0;
        $temp_refen = 0;
        foreach ($year_order_res as $key => $value) {

          if($v[0] <= $value['add_time']*1 && $v[1] >= $value['add_time']*1){
            // dump($value['order_amount']);
            $temp_order ++;
            if(in_array($value['order_status'], [4,6,8])){
              $temp_refen += $value['order_amount']*1;
            }elseif(in_array($value['order_status'], [3,5,7])){
              $temp_price += $value['order_amount'] * 1;
            }
          }
        }
        array_push($price_data, $temp_price);
        array_push($order_data, $temp_order);
        array_push($refen_data, $temp_refen);
      }
      $data = [ 'price'=>$price_data, //月销售额
                'orderNum'=>$order_data, //月订单数
                'refen'=>$refen_data, //月退款金额
                'sale_succ' => $sale_total_succ, //交易金额
                'order_count' => $order_count, //订单数量
                'order_succ'  => $order_succ, //成功交易
                'order_fail'  => $order_fail, //交易失败
                'order_refen' => $sale_order_fail, //退款
              ];

      return $this->fetch('order_count', $data);
  }

  /**
   * 当月每天用户和订单数
   * @return   boolean                  [description]
   * @Author:  slade
   * @DateTime :2017-05-06T12:16:08+080
   */
  public function thisMonthAnalyse(){
    $firstday = date('Y-m-01', time());  //第一天
    $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day")); //最后一天

    $lastday_num = (int)date('d', strtotime($lastday));
    $user_wh['createtime'] = ['between', [strtotime($firstday), strtotime($lastday)]];
    $user_res = Db::name('user')->field(['createtime'])->where($user_wh)->select(); //用户信息

    $order_wh['add_time'] = ['between', [strtotime($firstday), strtotime($lastday)]];
    $order_res = Db::name('order')->field(['add_time', 'order_id'])->where($order_wh)->select();

    $data = [];
    $day = [];
    for($i=1;$i<=$lastday_num;$i++){
      $temp_time = strtotime(date("Y-m-{$i}", time())); // 当天 0 时
      $temp_end_time = strtotime(date("Y-m-{$i} 23:59:59", time()));
      $data['user_data'][date("Y-m-{$i}", time())] = 0;
      $data['order_data'][date("Y-m-{$i}", time())] = 0;
      $data['goods_data'][date("Y-m-{$i}", time())] = 0;
      $day[] = date("Y-m-{$i}", time());
    }

    foreach ($user_res as $key => $value) {
      $temp_time = date('Y-m-j', $value['createtime']);
      if(array_key_exists($temp_time, $data['user_data'])){
        $data['user_data'][$temp_time] ++;
      }
    }

    $order_ids = [];
    foreach ($order_res as $k => $v) {
      $temp_time = date('Y-m-j', $v['add_time']);
      if(array_key_exists($temp_time, $data['order_data'])){
        $data['order_data'][$temp_time] ++;
        // dump($temp_time);
        array_push($order_ids, $v['order_id']);
      }
    }
    // dump($data);
    $str_ids = '0';
    if(count($order_ids) != 0){
      $str_ids = implode(',',$order_ids);
    }
    // dump($str_ids);
    $map['order_id'] = ["exp", "IN (".$str_ids.")"];
    $goods_res = Db::name('order_goods')->field('createtime')->where($map)->select();
    foreach ($goods_res as $l => $m) {
      $temp_time = date('Y-m-j', $m['createtime']);
      if(array_key_exists($temp_time, $data['goods_data'])){
        $data['goods_data'][$temp_time] ++;
      }
    }

    $data['user_data'] = array_values($data['user_data']);
    $data['order_data'] = array_values($data['order_data']);
    $data['goods_data'] = array_values($data['goods_data']);
    // dump($data);exit;
    return $this->fetch('data-analyse', ['data'=>$data, 'day'=>$day]);
  }

  /**
   * 代理树
   * @author: slide
   */
  public function childTree($pid = ''){
    if($pid){
      $user_list = Db::name('user')->where('pid', $pid)->select();
    }else{
      $user_list = Db::name('user')->where('agent_type', 1)->select();
    }
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
  //==============================================新添加统计===============================================================================

  /**
   * 分成明细
   * @author: slide
   *
   * @param $start
   * @param $end
   *
   */
  public function devide_detail($start='', $end='',$page=1){

    $map=[];
    $where=[];
    if($start){
      $map['updatetime']=['between', [strtotime(date('Y-m-d 00:00:00',strtotime($start))), strtotime(date('Y-m-d 23:59:59', strtotime($start)))]];
      $where['ag.createtime']=['between', [strtotime(date('Y-m-d 00:00:00',strtotime($start))), strtotime(date('Y-m-d 23:59:59', strtotime($start)))]];
    }

    if($start && $end){
      $map['updatetime'] = ['between', [strtotime($start), strtotime($end)]];
      $where['ag.createtime'] = ['between', [strtotime($start), strtotime($end)]];
    }

    // 公司账户
    $employee_accout = model('user')->where('is_employ_agent', 1)->find();
    $employee_accout_id = $employee_accout ? $employee_accout['id'] : 0;
    // 新增

    $new_agent = Db::name('agent_apply')->where($map)->select();
    //dump(Db::name('agent'));
    $new_agent_count = count($new_agent);

    $new_agent_arr = [
      'first' => 0,
      'second'  => 0,
      'third'   => 0,
      'fourth'  => 0
    ];
    foreach ($new_agent as $k=>$v){
      switch ($v['agent_type']){
        case 1:
          $new_agent_arr['first'] ++;
          break;
        case 2:
          $new_agent_arr['second'] ++;
          break;
        case 3:
          $new_agent_arr['third'] ++;
          break;
        case 4:
          $new_agent_arr['fourth'] ++;
          break;
      }
    }
    //所有代理
    $userMdl = model('user');
    $user_res = $userMdl->where('agent_type > 0')->select();
    // 代理人数
    $agent_user_count = count($user_res);
    $user_agent_arr = [
      'first' => 0,
      'second' => 0,
      'third'  => 0,
      'fourth' => 0
    ];

    foreach ($user_res as $k=>$v){
      switch ($v['agent_type']){
        case 1:
          $user_agent_arr['first'] ++;
        break;
        case 2:
          $user_agent_arr['second'] ++;
        break;
        case 3:
          $user_agent_arr['third'] ++;
          break;
        case 4:
          $user_agent_arr['fourth'] ++;
          break;
      }
    }

    //列表
    $where['ag.user_id'] = ['<>', $employee_accout_id];
    $divide_list = Db::name('divide_note')->alias('ag')->field(['ag.*','u.nickname','u.sex','u.phone','u.agent_type','u.user_vb','wx.nickname as wx_nickname','wx.sex as wx_sex'])->join("__USER__ u",'u.id=ag.user_id','LEFT')->join("__USER_WEICHAT_INFO__ wx",'wx.id=u.bindwx','LEFT')->where($where)->order('ag.createtime desc')->paginate(Config::get('pageSize'), false, ['page'=>$page]);

    if($this->request->isAjax()){
      return $this->ajax(2000, '成功', '', ['new_agent_count'=>$new_agent_count,'new_agent_arr'=> $new_agent_arr,'agent_user_count'=> $agent_user_count,'user_agent_arr'=> $user_agent_arr,'divide_list'=> $divide_list]);
    }else{
      $this->assign('new_agent_count', $new_agent_count);  // 新增代理书
      $this->assign('new_agent_arr', $new_agent_arr); // 新增代理数组
      $this->assign('agent_user_count', $agent_user_count); //代理总人数
      $this->assign('user_agent_arr', $user_agent_arr); // 代理数组
      $this->assign('divide_list', $divide_list); // 分成列表
      return $this->fetch();
    }
  }

  /**
   * 商品分析
   * @author: slide
   */
  public function goodsAnalyse($start='', $end=''){
    $map=[];
    $where=[];
    if($start){
      $map['createtime']=['between', [strtotime(date('Y-m-d 00:00:00',strtotime($start))), strtotime(date('Y-m-d 23:59:59', strtotime($start)))]];
      $where['pay_time']=['between', [strtotime(date('Y-m-d 00:00:00',strtotime($start))), strtotime(date('Y-m-d 23:59:59', strtotime($start)))]];
    }

    if($start && $end){
      $map['createtime'] = ['between', [strtotime($start), strtotime($end)]];
      $where['pay_time'] = ['between', [strtotime($start), strtotime($end)]];
    }
    $orderMdl = model('order');
    $order_res = $orderMdl->field(['order_id'])->where($where)->select();

    $order_ids = implode(',', array_keys(convert_arr_key($order_res, 'order_id')));
    $data = [];
    $hot_sailer = [];
    $money_best = [];
    $profit_best = [];
    if($order_ids != ''){
      $order_goods = Db::name('order_goods')->where("order_id IN ($order_ids)")->select();
      foreach ($order_goods as $k => $v){
        // 热销
        $goods_ids = array_keys($data);
        if(in_array($v['goods_id'], $goods_ids)){
          $data[$v['goods_id']]['number'] += $v['goods_num'];
          $data[$v['goods_id']]['goods_total'] += $v['goods_total'];
          $data[$v['goods_id']]['profit'] += $v['profit'];
        }else{
          $data[$v['goods_id']] = [
            'goods_id'  =>$v['goods_id'],
            'goods_name'=>$v['goods_name'],
            'number' => $v['goods_num'],
            'goods_total' =>$v['goods_total'],
            'profit'  => $v['goods_num'] * $v['profit']
          ];
        }
      }
      $hot_sailer = array_arr_sort($data, 'number')[0];
      // 销售金额
      $money_best = array_arr_sort($data, 'goods_total')[0];
      // 利润最多
      $profit_best = array_arr_sort($data, 'profit')[0];
    }

//  exit;
    //dump($data);
    //dump(array_arr_sort($data, 'number'));exit;
    // 热销

    //dump($hot_sailer);
    return $this->fetch('goods-analyse', [
      'hot_sailer' => $hot_sailer,
      'money_best' => $money_best,
      'profit_best'=> $profit_best
    ]);
  }

  /**
   * 销售统计
   * @author: slide
   *
   * @param string $start_time
   * @param string $end_time
   *
   */
  public function sailer_count($start='', $end=''){
    $map=[];
    $order=[];
    if($start){
      $map['createtime'] = ['between', [strtotime($start), time()]];
      $order['pay_time'] = ['between', [strtotime($start), time()]];
      $time_string=date("Y-m-d", strtotime($start))."至".date("Y-m-d", time());
    }
    if($end){
      $map['createtime'] = ['between',[strtotime($end),strtotime($end)+86400000]];
      $order['pay_time'] = ['between',[strtotime($end),strtotime($end)+86400000]];
      $time_string=date("Y-m-d", strtotime($end))."至".date("Y-m-d", strtotime($end)+86400000);
    }
    if($start && $end){
      $map['createtime'] = ['between', [strtotime($start), strtotime($end)]];
      $order['pay_time'] = ['between', [strtotime($start), strtotime($end)]];
      $time_string=date("Y-m-d", strtotime($end))."至".date("Y-m-d", strtotime($end)+86400000);
    }


    // 支付过的订单
    //$map['pay_status'] = 1;
    $order_res = Db::name('order')->field(['order_id','order_amount','total_amount','user_id'])->where("order_status IN (2,3,5,7) AND pay_status=2 AND is_distribut=1")->where($order)->select();
    //dump(Db::name('order')->getLastSql());
    //dump($order_res);
    $order_ids = implode(',', array_keys(convert_arr_key($order_res, 'order_id')));

    // 折扣价
    $zekou = 0;
    foreach ($order_res as $k => $v){
      $zekou += $v['total_amount'] - $v['order_amount'];
    }
    //dump($zekou);
    $divide_res = 0;
    $total_profit = 0;
    $total_divide = 0;
    $total_company = 0;
    if($order_ids != ''){
      // 查公司账户
      $company_account = model('user')->where('is_employ_agent', 1)->find();
      $company_account_id = $company_account ? $company_account['id'] : 0;
      // 查分成
      $divide_res = Db::name('divide_note')->field(['user_id','total_money','type'])->where("order_id IN ($order_ids)")->select();

      foreach ($divide_res as $k => $v){
        if($v['user_id'] == $company_account_id){
          $total_company += $v['total_money'];
        }else{
          $total_divide += $v['total_money'];
        }
      }
      // 查利润
      $profit = Db::name('order_goods')->field(['order_id','goods_num','profit'])->where("order_id IN ($order_ids)")->select();
      //dump($profit);
      foreach ($profit as $k => $v){
        $total_profit += $v['goods_num'] * $v['profit'];
      }

    }

    // 总收入
    $total_profit = $total_profit;
    // 下单会员数
    $total_user = 0;
    $user_arr = [];
    foreach ($order_res as $k => $v){
      if(!in_array($v['user_id'], $user_arr)){
        $total_user ++;
        array_push($user_arr, $v['user_id']);
      }
    }

    // 订单数
    $order_total = count($order_res);

    $this->assign('total_money', $total_profit); // 总收入
    $this->assign('buy_user_count', $total_user); // 支付买家数
    $this->assign('total_order', $order_total); // 订单数
    $this->assign('out_count', ($total_divide + $zekou)); // 支出
    $this->assign('company_account', $total_company); //公司结余
    return $this->fetch();
  }

  /**
   * 代理详情
   * @author: slide
   *
   * @param        $type
   * @param string $start_time
   * @param string $end_time
   * @param int    $page
   *
   * @return mixed
   * @throws \think\exception\DbException
   *
   */
  public function agent_detail($type, $start_time='', $end_time='', $page=1){
    $map = [];
    $time_string="";
    if($start_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['a.createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
    }
    if($start_time && $end_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
    }

    // 代理申请并通过
    if($type == 0){
      $user = new User();
      $map['a.agent_type'] = 0;
      $user_list = $user->alias('a')->field(['a.*','u.nickname as tjr_nickname','u.phone as tjr_phone','wx.nickname as wx_nickname', 'wxu.nickname as tjr_wx_nickname'])
        ->join('__USER__ u', 'u.id=a.pid', 'left')
        ->join('__USER_WEICHAT_INFO__ wx','wx.id=a.bindwx','left')
        ->join('__USER_WEICHAT_INFO__ wxu','wxu.id=u.bindwx','left')
        ->where($map)->order('a.createtime desc')->paginate(Config::get('pageSize'), false, ['page'=>$page, 'query'=>['type'=>$type, 'start_time'=>$start_time,'end_time'=>$end_time]]);
    }else{
      $map['a.agent_type'] = $type;
      $user_list = Db::name('agent_apply')->alias('a')->field(['a.id','a.user_id','a.agent_type','a.createtime','a.updatetime','a.money','u.nickname','u.phone','u.bindwx','wx.nickname as wx_nickname','uu.nickname as tjr_nickname','uu.phone as tjr_phone','wxu.nickname as tjr_wx_nickname'])
        ->join('__USER__ u','u.id=a.user_id','LEFT')
        ->join('__USER__ uu', 'uu.id=u.pid', 'left')
        ->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','left')
        ->join('__USER_WEICHAT_INFO__ wxu','wxu.id=uu.bindwx','left')
        ->where($map)->where('a.status=1')
        ->paginate(Config::get('pageSize'), false, ['page'=>$page, 'query'=>['type'=>$type, 'start_time'=>$start_time,'end_time'=>$end_time]]);
    }
    return $this->fetch('agent_detail',['list' => $user_list,'type'=>$type, 'time_string'=>$time_string]);
  }

  /**
   * 分成详情
   * @author: slide
   *
   * @param int    $type 1、购买商品分成2、代理申请分成3、分红补贴4、团队奖励，5l领导人津贴
   * @param string $start_time
   * @param string $end_time
   * @param int    $page
   *
   * @return mixed
   *
   */
  public function divide_detail($type=1, $start_time='', $end_time='', $page=1){
    $map = [];
    $time_string="";
    if($start_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['a.createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
    }
    if($start_time && $end_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
    }

    $user_company = model('user')->where('is_employ_agent',1)->value('id');
    $user_company = $user_company ? $user_company : 0;
    if($type == 3){
      $agent_detail = Db::name('divide_note')->alias('a')->field(['a.id','a.user_id','a.order_id','a.type','a.total_money','a.createtime','a.user_info','a.order_info','u.nickname','u.agent_type','u.phone','wx.nickname as wx_nickname','uu.phone as from_user_phone','uu.nickname as from_user_nickname','uu.agent_type as from_user_agent_type','wxu.nickname as from_userwx_nickname','o.order_sn'])
        ->join('__USER__ u','u.id=a.user_id','LEFT')
        ->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx', 'left')
        ->join('__ORDER__ o','o.order_id=a.order_id', 'left')
        ->join('__USER__ uu','uu.id=o.user_id','LEFT')
        ->join('__USER_WEICHAT_INFO__ wxu','wxu.id=uu.bindwx', 'left')
        ->where($map)->where('a.type='.$type.' AND a.user_id <> '.$user_company)
        ->order('a.createtime desc')
        ->paginate(Config::get('pageSize'), false, ['page'=>$page,'query'=>['type'=>$type, 'start_time'=>$start_time,'end_time'=>$end_time]]);

    }else{
      $agent_detail = Db::name('divide_note')->alias('a')->field(['a.id','a.user_id','a.order_id','a.type','a.total_money','a.createtime','a.user_info','a.order_info','u.nickname','u.agent_type','u.phone','wx.nickname as wx_nickname','uu.phone as from_user_phone','uu.nickname as from_user_nickname','uu.agent_type as from_user_agent_type','wxu.nickname as from_userwx_nickname'])
        ->join('__USER__ u','u.id=a.user_id','LEFT')
        ->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx', 'left')
        ->join('__AGENT_APPLY__ o','o.id=a.order_id', 'left')
        ->join('__USER__ uu','uu.id=o.user_id','LEFT')
        ->join('__USER_WEICHAT_INFO__ wxu','wxu.id=uu.bindwx', 'left')
        ->where($map)->where('a.type='.$type.' AND a.user_id <> '.$user_company)
        ->order('a.createtime desc')
        ->paginate(Config::get('pageSize'), false, ['page'=>$page,'query'=>['type'=>$type, 'start_time'=>$start_time,'end_time'=>$end_time]]);

      /*$agent_detail = Db::name('divide_note')->alias('a')->where($map)->where('a.type='.$type.' AND a.user_id <> '.$user_company)
        ->order('a.createtime desc')
        ->paginate(Config::get('pageSize'), false, ['page'=>$page,'query'=>['type'=>$type, 'start_time'=>$start_time,'end_time'=>$end_time]]);*/
    }
    //dump($agent_detail);
//    dump(Db::name('divide_note')->getLastSql());
    return $this->fetch('divide_detail', ['list'=>$agent_detail, 'type'=>$type,'time_string' => $time_string]);
  }

  /**
   * @author: slide
   *
   * @param string $start_time
   * @param string $end_time
   * @param int    $page
   *
   * @return mixed
   *
   */
  public function goods_detail($start_time='', $end_time='', $page=1){
    $map = [];
    $order = [];
    $time_string="";
    if($start_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
      $order['o.pay_time'] = ['between', [strtotime($start_time), time()]];;
    }
    if($end_time){
      $map['a.createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
      $order['o.pay_time'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];

    }
    if($start_time && $end_time){
      $map['a.createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
      $order['o.pay_time'] = ['between',[strtotime($start_time),strtotime($end_time)]];
    }

    // 有效订单
    $order_res = Db::name('order_goods')
    ->alias('og')->field(['og.*','o.order_sn','o.pay_status','o.order_status','o.shipping_status'])
    ->join('__ORDER__ o','o.order_id=og.order_id','left')
    ->where($order)
    ->paginate(Config::get('pageSize'), false, ['page'=>$page, 'query'=>[ 'start_time'=>$start_time,'end_time'=>$end_time]]);

    return $this->fetch('goods_detail',['list' => $order_res, 'time_string' => $time_string]);

  }

  /**
   * 充值
   * @author: slide
   *
   * @param string $start_time
   * @param string $end_time
   * @param int    $page
   *
   * @return mixed
   *
   */
  public function recharge_detail($start_time='', $end_time='', $page=1) {
    $map = [];
    $time_string="";
    if($start_time){
      $map['ar.createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['ar.createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
    }
    if($start_time && $end_time){
      $map['ar.createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
    }

    $list = Db::name('apply_recharge')->alias('ar')->field(['ar.*','u.nickname','u.phone','wx.nickname as wx_nickname'])->join('__USER__ u','u.id=ar.user_id','left')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','left')->where($map)->where('ar.status=1')->paginate(Config::get('pageSize'), false, ['page'=>$page]);

    return $this->fetch('recharge_detail', ['list' => $list, 'time_string' => $time_string]);

  }

  /**
   * 申请提现
   * @author: slide
   *
   * @param string $start_time
   * @param string $end_time
   * @param int    $page
   *
   * @return mixed
   *
   */
  public function sqtx_detail($start_time='', $end_time='', $page=1) {
    $map = [];
    $time_string="";
    if($start_time){
      $map['ar.createtime'] = ['between', [strtotime($start_time), time()]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", time());
    }
    if($end_time){
      $map['ar.createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400]];
      $time_string=date("Y-m-d", strtotime($end_time))."至".date("Y-m-d", strtotime($end_time)+86400);
    }
    if($start_time && $end_time){
      $map['ar.createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
      $time_string=date("Y-m-d", strtotime($start_time))."至".date("Y-m-d", strtotime($end_time));
    }

    $list = Db::name('usersqtx')->alias('ar')->field(['ar.*','u.nickname','u.phone','wx.nickname as wx_nickname'])->join('__USER__ u','u.id=ar.userid','left')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','left')->where($map)->where('ar.isok=1')->paginate(Config::get('pageSize'), false, ['page'=>$page]);

    return $this->fetch('sqtx_detail', ['list' => $list, 'time_string' => $time_string]);
  }
}
