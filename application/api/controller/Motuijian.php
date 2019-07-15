<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/7 0007
 * Time: 上午 9:10
 */

namespace app\api\controller;


use think\Db;

class Motuijian {

  /**
   * 享受下级直接推荐的5%收入
   * @methods
   * @desc
   * @author slide
   *
   * @param int   $user_pid       用户上级id
   * @param int   $totalMoney     用户获得的收入
   * @param array $user_info      用户信息
   * @param array $order_info     用户消费信息
   *
   */
  public static function diectReword ($user_pid = 0, $totalMoney = 0, $user_info = [], $order_info = []) {
    // 直推奖励【团队奖励】
    $user_parent_info   = Db::name( 'user' )->field( [ 'nickname', 'phone', 'agent_type', 'id', 'pid', 'bindwx', 'parent_agent', 'user_vb', 'is_cansharered' ] )->find( $user_pid);
    $agent_divide_money = $totalMoney;
    if ( $user_parent_info[ 'pid' ] && $user_parent_info[ 'pid' ] != '' ) {
      $leader_info = model( 'user' )->where( 'id', $user_parent_info[ 'pid' ] )->field( 'id,pid,agent_type,is_employ_agent' )->find();
      if($leader_info['is_employ_agent'] == 1) return;
      if ( $leader_info ) {
        $leader_money = $agent_divide_money * 0.05;
        divideNote( [ 'user_id' => $leader_info[ 'id' ], 'order_id' => 0, 'commission' => 0, 'total_money' => $leader_money, 'level' => 0, 'type' => 4, 'user_info' => json_encode( $user_info ), 'order_info' => json_encode( $order_info ) ] );
        accountLog( $leader_info[ 'id' ], $leader_money, 1, '您有一笔新的团队奖励到账啦' );
      }
    }
  }
}
