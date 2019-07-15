<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/19 0019
 * Time: 下午 2:34
 */

namespace app\api\controller;

use app\common\model\Order;
use think\Config;
use think\Controller;
use think\Db;
use app\common\model\User;

/**
 * 计划任务 api
 * Class Task
 * @package app\api\controller
 */

class Task extends Controller {
  protected function _initialize () {
    parent::_initialize();
  }
  
  /**
   * 每天给区域代理分红 // 每日0点执行任务
   * @author: slide
   */
  public function everyDaytoAgent ($time = '') {
    
   L('执行定时了任务');
    
    // 1、计算公司当日的总利润
      // 1> 已购买商品利润 - 分成
    $need_time = $time ? $time : time();
    $orderMdl = new Order();
    $order_where['pay_time'] = ['between', [strtotime(date('Y-m-d'), $need_time), strtotime(date('Y-m-d 23:59:59'), $need_time)]];
    $order_where['is_distribut'] = 1;
    $order_res = $orderMdl->field(['order_id', 'order_amount', 'distribut_total','actual_distribut'])->where($order_where)->select();
    $today_total_distribut = 0;
    $order_ids = '';
    foreach ($order_res as $k => $v){
      $today_total_distribut += $v['actual_distribut'];
      $order_ids .= $v['order_id'].',';
    }
    $order_ids = trim($order_ids, ',');
    // 查询并加出利润
    $order_goods = [];
    if($order_ids != ''){
      $order_goods = Db::name('order_goods')->field(['profit', 'goods_num'])->where("order_id IN ($order_ids)")->select();
    }
    //dump($order_goods);
    $total_profit = 0;
    foreach ($order_goods as $k => $v){
      $total_profit += $v['goods_num'] * $v['profit'];
    }
    $last_profit = $total_profit - $today_total_distribut;  // 当天购买商品最后的利润
    
    //dump('当天购物总利润'.$total_profit);
    //dump('当天购物分成'.$today_total_distribut);
    //dump($last_profit);
    
    // 2> 申请代理 - 分成
    $agent_where['updatetime'] = ['between', [strtotime(date('Y-m-d'), $need_time), strtotime(date('Y-m-d 23:59:59'), $need_time)]];
    $agent_where['status'] = 1;
    $agent_where['is_distribut'] = 1;
    //当日审核完成的金额总
    $today_agent_apply = Db::name('agent_apply')->field(['total_distribue','actual_distribut', 'money'])->where($agent_where)->select();
    //dump($today_agent_apply);
    $today_agent_distribut = 0;
    $today_agent_money = 0;
    foreach ($today_agent_apply as $key => $value){
      $today_agent_distribut += $value['actual_distribut'];
      $today_agent_money += $value['money'];
    }
    //dump('当天代理申请'.$today_agent_money);
    //dump('当天代理申请分成'.$today_agent_distribut);
    // 代理申请公司总利润
    $today_agent_total_profit = $today_agent_money - $today_agent_distribut;
    
    // 2、统计区域代理人数
    $agent_first_user = Db::name('user')->where('agent_type', 1)->select();
    //人数
    $count_agent_first = count($agent_first_user);
    
    // 3、平均分给每个用户
    $total_all_profit = ($last_profit + $today_agent_total_profit) / $count_agent_first;
    if($total_all_profit <= 0){
      writeTaskNote(request()->action(), 1, 1, '当天利润为负或者为0，不进行分成');
    }
    L('每人分'.$total_all_profit);
    //dump($total_all_profit);exit;
    //exit;
    Db::startTrans();
    try{
      foreach ($agent_first_user as $n => $m){
        // 分钱到账户
        accountLog($m['id'], $total_all_profit, 1, date('Y年m月d日',$need_time).'区域代理每日公司分红收入');
      }
      Db::commit();
      writeTaskNote(request()->action(), 1, 1, date('Y-m-d', $need_time).'分成成功');
    }catch (\Exception $e){
      Db::rollback();
      L('错误'.$e);
      recordTaskFaile($need_time, '/api/task/everyDaytoAgent');
      writeTaskNote(request()->action(), 0, 1, date('Y-m-d', $need_time).'分成失败');
      L(date('Y年m月d日',$need_time).'分成失败');
    }
  }
  
  /**
   * 每月给代理分成下级代理的5%
   * @author: slide
   *
   */
  public function everyMonthtoAgent($time = ''){
    $time = $time ? $time : time();
    $first_day = date('Y-m-01', strtotime(date("Y-m-d", $time)));
    $last_day = date('Y-m-d 23:59:59', strtotime("{$first_day} +1 month -1 day"));
    // 1、查询下级代理的用户
    Db::startTrans();
    try{
      // 所有的区域代理
      $agent_first_user = Db::name('user')->where('agent_type', 1)->select();
      $agent_user_ids = implode(',', array_keys( convert_arr_key($agent_first_user, 'id') ));
      // 查询这些代理下所有的代理
      $agent_child_user = [];
      if($agent_user_ids != ''){
        $agent_child_user = Db::name('user')->where("parent_agent IN ($agent_user_ids) AND agent_type <> 0")->select();
        L('下级用户'.json_encode($agent_child_user, true));
      }
      
      $wh['createtime'] = ['between', [strtotime($first_day), strtotime($last_day)]];
      foreach ($agent_first_user as $key => &$value){
        $temp_ids = '';
        $total_to_agent_first = 0;
        foreach ($agent_child_user as $k => $v) {
          if($value['id'] == $v['parent_agent']){
            $temp_user_money = 0;
            $value['child_agent'][] = $v;
            $temp_ids.=$v['id'].',';
            //每个下级代理用户当月分成收入
            $temp_user_money = Db::name('divide_note')->where("user_id={$v['id']}")->where($wh)->sum('total_money');
            
            $temp_to_agent = $temp_user_money * (Config::get('every_month_divide') * 1);
            
            $total_to_agent_first += $temp_to_agent;
            
            // 减去当前用户的代理收入 （有可能余额不足）
            accountLog($v['id'], $temp_to_agent, 2, '每月收入的'. (Config::get('every_month_divide') * 100) ."%需要分给上级区域代理");
          }
        }
        
        // 查询当前这个区域代理下级的当月分成总金额
        $user_divide_total_divide = $total_to_agent_first;
        accountLog($value['id'],$user_divide_total_divide, 1, '所有下级代理会员本月贡献'.(Config::get('every_month_divide') * 100) . '%的收入');
      }
      Db::commit();
      writeTaskNote(request()->action(), 1, 2, date('Y-m', $time).'下级向上级贡献分成成功');
    }catch (\Exception $e){
      Db::rollback();
      L('本月下级向上级贡献收入失败', 'warn');
      writeTaskNote(request()->action(), 0, 2, date('Y-m', $time).'下级向上级贡献分成失败');
      recordTaskFaile(time(), '/api/task/everyDaytoAgent');
    }
  }
  
  /**
   * 每天省级代理从直接下级省级分代理招商收入的5%
   * @author: slide
   */
  public function firstAgentFive($time=''){
    $path = request()->module().'/'.request()->controller().'/'.request()->action();
    // 取出所有的省级代理
    $all_first_agent = Db::name('user')->where('agent_type', 1)->select();
    // 取出无上级的省级代理和有上级的省级
    if($time){
      $where['createtime'] = ['between', [strtotime(date('Y-m-d 0:0:0', $time)), strtotime(date('Y-m-d 23:59:59'), $time)]];
    }else{
      $where['createtime'] = ['between', [strtotime(date('Y-m-d 0:0:0', time())), strtotime(date('Y-m-d 23:59:59'), time())]];
    }
    // 递归组合用户数据
    $all_first_agent = agent_child($all_first_agent);
//    dump($all_first_agent);exit;
  
    // dump($all_first_agent);exit;
    if(agent2anget($all_first_agent, $where)){
      writeTaskNote($path, 1, '每天下级省级代理招商分成任务成功');
      L('每日公司利润分成成功');
      return json(['code'=>2000, 'msg'=>'每天下级省级代理招商分成任务成功']);
    }else{
      writeTaskNote($path, 0, '每天下级省级代理招商分成任务失败');
      return json(['code'=>4000, 'msg'=>'每天下级省级代理招商分成任务失败']);
    }
  }
  
  /**
   * 省级代理每天从公司的利润中获取40%的平均
   * @author: slide
   */
  public function FirstAgentCompany($time = ''){
    $map = [];
    $create_time = [];
    if($time){
      $map['pay_time'] = ['between', [strtotime(date('Y-m-d 0:0:0', $time)), strtotime(date('Y-m-d 23:59:59', $time))]];
      $create_time['createtime'] = ['between', [strtotime(date('Y-m-d 0:0:0', $time)), strtotime(date('Y-m-d 23:59:59', $time))]];
    }else{
      $time = time();
      $map['pay_time'] = ['between', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))]];
      $create_time['createtime'] = ['between', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))]];
    }
    //dump($create_time);
    // 计算公司利润
    // 1、商品利润
    $order_res = Db::name('order')->where("order_status IN (2,3,5,7) AND pay_status=2 AND is_distribut=1")->where($map)->select();
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
    $profit = 0; //利润
    // 商品利润
    $order_goods = [];
    if($order_ids != ''){
      $time_arr = $create_time['createtime'];
      unset($create_time['createtime']);
      $create_time['og.order_id'] = ["exp", "IN (".$order_ids.")"];
      $order_goods = Db::name('order_goods')->alias('og')->field(['og.profit','og.goods_name','og.goods_num','og.goods_total','og.goods_price', 'o.is_distribut','o.order_sn'])->join('__ORDER__ o','o.order_id=og.order_id','LEFT')->where($create_time)->select();
      // dump(Db::name('order_goods')->getLastSql());
      foreach ($order_goods as $key => $value){
        $profit += $value['profit'] * $value['goods_num'];
        $order_count_number += $value['goods_num'];
        //$order_count_price += $value['goods_total'];
      }
      unset($create_time['og.order_id']);
      $create_time['order_id'] = ["exp", "IN (".$order_ids.")"];
      $create_time['createtime'] = $time_arr;
    }
  
    // 查出公司账户
    $company_account = Db::name('user')->where('is_employ_agent', 1)->find();
    $company_account_id = $company_account ? $company_account['id'] : 0;
  
    // 购买商品分成 type = 1
    $create_time['type'] = 1;
    // 排除 公司账户
    //$map['user_id'] = ['<>', $company_account_id];
  
    $divide_res = Db::name('divide_note')->where($create_time)->select();
    //$divide_count = count($divide_res);
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
    // 商品销售最终利润
    unset($create_time['type']);
    $profit = $profit - ($order_count_price - $goods_real_amount)-$divide_price;
    // ======================================
    // 2、代理利润
  
    // 代理申请并通过
    // 分成
    $create_time['type'] = 2;
    unset( $create_time['order_id'] );
    //dump($create_time);
    $total_agent_divide = Db::name('divide_note')->where($create_time)->select();
      //dump(Db::name('divide_note')->getLastSql());
  
      $divide_count = 0;
    $divide_price = 0;
    $agent_company_account_price = 0;
    foreach ($total_agent_divide as $k=>$v){
      // 排除 公司账户
      if($v['user_id'] == $company_account_id){
        $agent_company_account_price += $v['total_money'];
      }else{
        $divide_count ++;
        $divide_price += $v['total_money'];
      }
    }
    //dump($agent_company_account_price);
    // 总利润
    $total_profit = $agent_company_account_price + $profit;
    //dump($total_profit.'-'.date('Y-m-d', $time));exit;
    //===============================================
    // 5、获取所有的区域代理
    $agent_first_user = Db::name('agent_need_getmoney')->select();
    // 总计算
    $everyone_money = floor(($total_profit * Config::get('every_agent_company')) / count($agent_first_user));
    
    //dump($everyone_money);exit;
    if($everyone_money < 0){
      // 发信息警示
      $weichat = new \app\home\controller\Weichat;
      $weichat->sendTemplateMsg('','TASK_PROCESSING',['今日分成利润'.$everyone_money,'利润分成错误','急需处理','起床起床'],542);
    }
    $path = $this->request->module().'/'. $this->request->controller().'/'.$this->request->action();
    
    Db::startTrans();
    try{
      foreach ( $agent_first_user as $k => $v ) {
        //给每一个省级代理分成
        L('剩余金额'.$v['money']);
        // 处理额度
        if($v['money'] == 0) continue;
    
        $money = $v['money'] != -1 && ($v['money'] - $everyone_money > 0) ? $everyone_money
          : $v['money'];
        // 更改额度
        if($v['money'] != -1){
          Db::name('agent_need_getmoney')->where('user_id', $v['user_id'])->setField('money',($v['money'] - $money));
          L('用户'.$v['user_id'].'分成'.$money);
          accountLog($v['user_id'],$money,1,'省级代理从公司利润分成收入');
          writeCompanyDivide($v['user_id'], $money, 2);
        }else{
          L('用户'.$v['user_id'].'分成'.$everyone_money);
          accountLog($v['user_id'],$everyone_money,1,'省级代理从公司利润分成收入');
          writeCompanyDivide($v['user_id'], $everyone_money, 2);
        }
       
        sendSiteMsg(-1,$v['user_id'],date('Y-m-d', $time).'公司利润分红提醒',date('Y年m月d日').'公司利润分红成功，快去查看您的账户记录吧！',3,false,false,false);
        L(date('Y-m-d',$time).$v['user_id'].'每日公司利润分成成功');
      }
      Db::commit();
      writeTaskNote($path, 1, '执行成功,金额'.$everyone_money);
      return json(['code'=>2000, 'msg'=>'每日公司利润分成成功']);
    }catch (\Exception $e){
      L('每日分成功40%公司利润错误'.$e);
      Db::rollback();
      writeTaskNote($path, 0, '执行失败');
      return json(['code'=>4000, 'msg'=>'每日公司利润分成失败']);
    }
    
  }
  
  public function agentGetFivePointProfit(){
    // 时间标识
    $date = thisMonth();
    
    //
  }
}
