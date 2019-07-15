<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/31 0031
 * Time: 下午 3:38
 */

namespace app\home\controller;
use think\Db;
use think\Session;

/**
 * 用户业绩类
 * Class UserAchive
 *
 * @package app\home\controller
 */
class UserAchive extends Validate {

  /**
   * 业绩统计
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public function index(){

    // 业绩
    $divide_res = Db::name('divide_note')->where('user_id', Session::get('qt_userId'))->select();

    $divide_result = [];
    $total = 0;
    foreach ($divide_res as $k => $v) {
      if(isset($divide_result[$v['type']])) {
        $divide_result[$v['type']] += $v['total_money'];
      }else{
        $divide_result[$v['type']] = $v['total_money'];
      }
      $total += $v['total_money'];
    }

    // 订货额
    $user_coupon = model('user')->where(['id' => Session::get('qt_userId')])->value('order_amount');
    $user_coupon = is_null($user_coupon) ? 0 : $user_coupon;
    //$total += $user_coupon;

    return $this->fetch('', ['user_coupon' => $user_coupon, 'divide_result' => $divide_result, 'total' => $total]);
  }

  /**
   * 业绩列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function achive_list($type = 1, $start = '', $end = '', $page = 1) {
    if(!$type) return $this->error('缺少必要参数');
    $data = [];
    switch ($type){
      case 1:
      case 2:
      case 3:
      case 4:
        $data = $this->divideList($type, $page, $start, $end);
        $where['user_id'] = Session::get('qt_userId');
        $where['type'] = $type;
        $totalNumber = Db::name('divide_note')->where($where)->sum('total_money');
        break;
      case 5:
        $data = $this->userAmountList($page, $start, $end);
        $user_coupon = model('user')->where(['id' => Session::get('qt_userId')])->value('order_amount');
        $totalNumber = is_null($user_coupon) ? 0 : $user_coupon;
        break;
    }
    $date = to_sex_month(true);
    $result = [];
    foreach ($date as $k => $v) {
      $temp_data = [];
      $total = $type*1 < 5 ? 0 : ['reduce' => 0, 'add' => 0];
      foreach ($data as $key => $val) {
        $val_time = $type*1 < 5 ? $val['createtime'] : $val['updatetime'];
        if($val_time >= intval($v[0]) && $val_time < intval($v[1])){
          $temp_data[] = $val;
          if($type < 5) {
            $total += $val['total_money'];
          }else{
            if($val['change_symbol'] == '-') $total['reduce'] += $val['change_num'];
            elseif($val['change_symbol'] == '+') $total['add'] += $val['change_num'];
          }
        }
      }
      if(count($temp_data) > 0) {
        $temp = [
          'date' => date('Y年m月', $v[0]),
          'data' => $temp_data,
          'total' => $total
        ];
        $result[] = $temp;
      }
    }
    $text = [1 => '商品分成', 2 => '销售业绩', 3 => '市场分红', 4 => '团队奖励', 5 => '订货额'];
    return $this->fetch('achive_list', ['list' => $result, 'total' => $totalNumber, 'text' => $text[$type], 'type' => $type]);
  }

  /**
   * 分成列表
   * @methods
   * @desc
   * @author slide
   *
   * @param $type
   * @param $page
   * @param $start
   * @param $end
   *
   * @return false|mixed|\PDOStatement|string|\think\Collection
   *
   */
  public function divideList($type, $page, $start, $end){
    $where['type'] = $type;
    $where['user_id'] = Session::get('qt_userId');

    if($start != '' && $end == '') $where['createtime'] = ['>=', strtotime($start)];
    elseif($start == '' && $end != '') $where['createtime'] = ['<=', strtotime($end)];
    elseif($start != '' && $end != '') $where['createtime'] = ['between', [strtotime($start), strtotime($end)]];

    $list = Db::name('divide_note')->where($where)->select();

    return $list;
  }

  /**
   * 订货额
   * @methods
   * @desc
   * @author slide
   *
   */
  public function userAmountList($page, $start, $end){
    $where['change_type'] = 3;
    $where['userid'] = Session::get('qt_userId');
    if($start != '' && $end == '') $where['updatetime'] = ['>=', strtotime($start)];
    elseif($start == '' && $end != '') $where['updatetime'] = ['<=', strtotime($end)];
    elseif($start != '' && $end != '') $where['updatetime'] = ['between', [strtotime($start), strtotime($end)]];

    $list = Db::name('balance_change')->where($where)->select();
    // paginate(12, ['page' => $page, 'query' => ['start' => $start, 'end' => $end]])

    return $list;

  }
}
