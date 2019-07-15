<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/1 0001
 * Time: 下午 9:25
 */

namespace app\api\controller;
use think\Db;
use think\Config;
/**
 * Class shareRed
 * 分红补贴
 * @package App\api\job
 */
class Sharered {

  protected static $start = null;
  protected static $end = null;

  public function __construct () {
    self::$start = is_null(self::$start) ? strtotime(date('Y-m-d 00:00', strtotime("-1 day"))) : self::$start;
    self::$end = is_null(self::$end) ? strtotime(date('Y-m-d 23:59', strtotime("-1 day"))) : self::$end;
  }

  /**
   * 计算利润
   * @methods
   * @desc
   * @author slide
   *
   */
  public function computedProfit () {
    L('到了计算利润');
    $profit = (new ComputeFree())->countProfit(self::$start, self::$end);
    L('利润计算完成'.$profit);
    return $profit;
  }

  /**
   * 获取代理
   * @methods
   * @desc
   * @author slide
   *
   */
  public function getAgent () {
    // 统计每个阶段的代理数
    /*$where['agent_type'] = ['<>', 0];
    $where['is_cansharered'] = 1;
    $user_agent_data = Db::name('user')->field('id,pid,phone,agent_type,is_cansharered,pid')->where($where)->select();*/
    $where['total_share_money'] = ['<>', 0];
    $user_agent_data = Db::name('agent_need_getmoney')->where($where)->select();

    return $user_agent_data;
  }

  /**
   * 计算每笔分成
   * @methods
   * @desc
   * @author slide
   *
   * @param $total_money
   * @return array
   */
  public function computedShare ($total_money, $total_share_need = 0) {
    // 根据代理身份计算分成
    // 处理代理数据
    $user_agent_data = $this->getAgent();
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

    // 1、
    // 总共需要分出去的钱
    $agent_info = Db::name('agent')->select();
    $agent_info = convert_arr_key($agent_info, 'id');
    //$total_need_divide_one = Config::get('share_red_one') * $total_money;
    //$total_need_divide_two = Config::get('share_red_two') * $total_money;

    $total_need_divide_one = $agent_info[1]['share_red_commission'] / 100 * $total_money; // 代理商
    $total_need_divide_two = $agent_info[2]['share_red_commission'] / 100 * $total_money; // 经销商

    // 计算每个等级每个人可以分多少钱
    $need_divide = [
      'one' => $total_need_divide_one / $total_count[1], // 代理商
      'two' => $total_need_divide_two / ($total_count[1] + $total_count[2]), // 经销商
    ];

    return $need_divide;
  }

  /**
   * 计算分红(每个人计算)
   * @methods
   * @desc
   * @author slide
   *
   */
  public function computedShareRed ($user_id = 0, $agent_type = 0, $money = 0, $total_share_money) {
    Db::startTrans();
    try{
      $total_money = $this->computedProfit();
      $need_divide = $this->computedShare($total_money, $total_share_money);
      L('分成比例'.var_export($need_divide, true));
      // $agent_type_now = $agent_type == 1 ? 'one' : 'two';
      $total_money_user = $need_divide['two'];

      $v= Db::name('user')->where('id', $user_id)->find();

      L('用户身份'.$agent_type);

      $agent_divide_total = $total_share_money;
      if($agent_type == 1) {
        $total_money_user += $need_divide['one'];
      }

      $divided_money = Db::name('share_red')->where('user_id', $v['id'])->find();

      if($agent_divide_total!=-1 && $money > $agent_divide_total) {
        $total_money_user = $agent_divide_total;
      }

      L('当前用户'.$user_id.'能分到的钱'.$total_money_user);
      L('分成信息'.var_export($divided_money, true));
      L('最大分成金额'.$agent_divide_total);

      // 判断用户已经分到的钱是否大于规定值或者为0则不分

      if($total_money_user <= 0 ) {
        L('商城利润不够分红');
        return true;
      }

      divideNote([
        'user_id' => $user_id,
        'order_id' => 0,
        'commission' => 0,
        'total_money' => $total_money_user,
        'level' => 0,
        'type' => 3,
        'user_info' => '',
        'order_info' => ''
      ]);

      if($divided_money){
        Db::name('share_red')->where('user_id', $user_id)->setInc('share_red_total', $total_money);
      }else{
        Db::name('share_red')->insert([
          'user_id'=> $v['id'],
          'share_red_total' => $total_money_user,
          'createtime' => time()
        ]);
      }

      // 增加已经分成额度
      $agent_divide_total != -1 && Db::name('agent_need_getmoney')->where('user_id', $user_id)->setInc('money', $total_money_user);

      accountLog($v['id'], $total_money_user, 1, '商城利润分红到账啦！');

      Db::commit();
      return true;

    }catch (\Exception $e) {
      L('分红补贴错误'. $e);
      Db::rollback();

      return false;
    }
  }

  /**
   * 创建人物
   * @methods
   * @desc
   * @author slide
   *
   */
  public function createJob(){
    $agent = $this->getAgent();
    foreach ($agent as $v) {
      call_user_func_array(['\\app\\api\\controller\\queue', 'addQueue'], [Config::get('queue')['moShareRed'],'moShareRed',['user_id' => $v['user_id'], 'agent_type' => $v['agent_type'], 'money' => $v['money'], 'total_share_money' => $v['total_share_money']]]);
      //$this->createCountJob($v['id']);
    }
  }
}
