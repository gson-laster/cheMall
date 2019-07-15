<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/12 0012
 * Time: 上午 10:09
 */

namespace app\home\controller;


use think\Db;
use think\Session;

class Agentreword extends Validate {

  protected function _initialize() {
    parent::_initialize();
  }

  /**
   * 统计用户奖励
   * @author slide
   * @param        $user_id
   * @param string $start
   * @param string $end
   * @return array
   *
   */
  public function countreword($user_id, $start = '', $end = '') {
    $where = [];
    if($start) $start = strtotime($start);
    if($end) $end = strtotime($end);
    if($start && $end) {
      $where['between'] = ['between', [$start, $end]];
    }else{
      if ($start) {
        $where['createtime'] = ['between', [$start, time()]];
      }

      if($end) {
        $where[ 'createtime' ] = [ '<=', $end ];
      }
    }
    $where['user_id'] = $user_id;
    // 统计所有的奖励
    $divide_note = Db::name('divide_note')->field('type,total_money')->where($where)->select();
    $result = [
      1 => 0,  // 购买商品分成
      2 => 0,  // 推荐代理分成
      3 => 0,  // 分红补贴
      4 => 0,  // 团队奖励
      5 => 0   // 领导人津贴
    ];
    $total = 0;
    foreach ($divide_note as $k => $v) {
      $result[$v['type']] += $v['total_money'];
      $total += $v['total_money'];
    }
    $result['total'] = $total;
    if($this->request->isAjax()) {
    	return $this->ajax(2000, '成功', '', $result);
    }else{
    	return $this->fetch('index', ['data' => $result]);
    }
  }

  /**
   * 用户奖励详情
   * @methods
   * @desc
   * @author slide
   *
   */
  public function rewordsDetail () {
    $where['createtime'] = ['between', [thisYear('start'), thisYear('end')]];
    $where['user_id'] = Session::get('qt_userId');
    $result = Db::name('divide_note')->where($where)->select();
    $month = to_sex_month(true);
    $data = [];
    $all = 0;
    foreach ( $month as $key ) {
      $temp_data = [];
      $total = 0;
      foreach ($result as $k => $v){
        if($v['createtime'] >= intval($key[0]) && $v['createtime'] < intval($key[1])){
          $temp_data[] = $v;
          $total += $v['total_money'];
        }
      }
      $temp = [
        'date' => date('Y年m月', $key[0]),
        'data' => $temp_data,
        'total' => $total
      ];
      $data[] = $temp;
      $all += $total;
    }
    $data['all_total'] = $all;
     // dump($data);
    return $this->fetch('rewordsDetail', ['data' => $data]);
  }

}
