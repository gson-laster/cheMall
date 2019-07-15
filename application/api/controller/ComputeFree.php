<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5 0005
 * Time: 上午 10:01
 */

namespace app\api\controller;

use app\common\model\Order;
use think\Db;

class ComputeFree {

  /**
   * 当前时间段的商品销售总额
   * @author slide
   * @param $start
   * @param $end
   * @return array 返回总数和利润
   */
  public function countGoodsTotalFree($start = '', $end = '') {
    // 组合时间
    $where = [];
    if($start && $end) {
      $where['pay_time'] = ['between', [$start, $end]];
    }

    // 1、查询订单
    $order_res = Order::field('order_id,order_amount')->where($where)->select();
    // 2、统计商品价格
    $order_ids = [];
    $goods_total_free = 0;
    $goods_profit_total = 0;

    foreach ($order_res as $k => $v){
      $goods_total_free += $v['order_amount'];
      array_push($order_ids, $v['order_id']);
    }

    // 3、查询商品
    $order_ids_str = implode(',', $order_ids);
    if($order_ids_str != ''){
      $order_goods_res = Db::name('order_goods')->field('goods_num,profit')->where("order_id IN ({$order_ids_str})")->select();

      // 计算利润
      foreach ($order_goods_res as $k => $v){
        $goods_profit_total += $v['goods_num'] * $v['profit'];
      }
    }
    L('商品计算结果'.$goods_profit_total);
    return ['goods_total_free' => $goods_total_free, 'goods_profit_total' => $goods_profit_total];
  }

  /**
   * 当前时间段分成的金额
   * @author slide
   * @param $start
   * @param $end
   * @param $type 查类型分成结果 1 商品分成 2 代理推荐分成 3 领导人分成
   * @return number 已经分成的金额
   */
  public function countDivideTotalFree($start = '', $end = '', $type = '') {
    $where = [];
    if($start&&$end){
      $where['createtime'] = ['between', [$start, $end]];
    }

    if($type) {
      $where['type'] = $type;
    }
    $company_account = Db::name('user')->where('is_employ_agent', 1)->value('id');

    $where['user_id'] = ['<>', $company_account];  // 去除公司账户

    $agent_des = Db::name('divide_note')->where($where)->sum('total_money');

    return $agent_des ? $agent_des : 0;
  }

  /**
   * 当前时间段的代理申请的金额
   * @author slide
   * @param $start
   * @param $end
   * @return number 返回代理申请的总金额(未分成)
   */
  public function countAgentApplyFree($start = '', $end = '') {
    $where['status'] = 1;
    if($start && $end) {
      $where['createtime'] = ['between', [$start, $end]];
    }

    // 结果
    $agent_apply = Db::name('agent_apply')->where($where)->sum('money');

    return $agent_apply ? $agent_apply : 0;
  }

  /**
   * 统计利润
   * @methods
   * @desc
   * @author slide
   * @return number 利润金额
   */
  public function countProfit($start = '', $end = '', $type = ''){
    L('计算利润的参数'.$start.$end);
    // 商品销售总额及利润
    $goods_free = $this->countGoodsTotalFree($start, $end);
    L('商品销售信息'.var_export($goods_free, true));
    // 代理申请总额
    $agent_free = $this->countAgentApplyFree($start, $end);
    L('代理申请信息'.var_export($agent_free, true));
    // 分成总金额
    $divide_free = $this->countDivideTotalFree($start, $end, $type);
    L('分成总金额'.var_export($divide_free, true));
    // 公司利润 = 商品利润 + 代理申请金额 - 分成金额
    $company_free = $goods_free['goods_profit_total'] + $agent_free - $divide_free;
    L('公司利润'.var_export($company_free, true));

    return $company_free;
  }
}
