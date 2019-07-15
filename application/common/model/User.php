<?php

namespace app\common\model;

use app\api\controller\Motuijian;
use app\common\Hook;
use app\home\controller\Weichat;
use think\Log;
use think\Model;
use think\Db;
/**
 * 用户表操作方法模型
 */
class User extends Model
{
  protected $createTime = 'createtime';
  protected $updateTime = 'updatetime';
  protected $insert = ['createtime'];
  protected $update = ['updatetime'];

  //自定义初始化
  protected function initialize()
  {
      //需要调用`Model`的`initialize`方法
      parent::initialize();
  }

  /**
   * 创建时间
   * @return bool|string
   */
  protected function setCreatetimeAttr() {
      return time();
  }

  /**
   * [setUpdatetimeAttr 更新时间]
   * @Author:  slade
   * @DateTime :2017-03-23T10:35:28+080
   */
  protected function setUpdatetimeAttr() {
      return time();
  }

  /**
   * [getStatusAttr 状态]
   * @param    [type]                   $value [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T10:57:21+080
   */
  public function getStatusAttr($value)
  {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
  }

  /**
   * [setStatusAttr 设置状态]
   * @param    [type]                   $value [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:51:13+080
   */
  public function setStatusAttr($value){
    $status = ['正常'=>1, '禁用'=> 0];
    return $status[$value];
  }

  /**
   * [getSexAttr 性别]
   * @param    [type]                   $value [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T10:58:17+080
   */
  public function getSexAttr($value)
  {
        $status = [1=>'男',2=>'女', 3=>'保密'];
        return $status[$value];
  }

  /**
   * [setSexAttr 设置性别]
   * @param    [type]                   $value [description]
   * @Author:  slade
   * @DateTime :2017-03-23T12:36:26+080
   */
  public function setSexAttr($value)
  {
        $status = ['男'=>1,'女'=>2, '保密'=>3];
        return $status[$value];
  }

  /**
   * [getCreatetimeAttr 获取创建时间]
   * @param    [type]                   $value [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T12:27:10+080
   */
  public function getCreatetimeAttr($value)
  {
        return date("Y-m-d H:i:s", $value);
  }

  /**
   * 获取代理类型
   * @param    [int]                   $id [用户id]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-22T10:14:16+080
   */
  public function getAgentType($id){
    return $this->where("id", $id)->value('agent_type');
  }

  /**
   * 获取父级信息
   * @param    [type]                   $uid [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T10:56:45+080
   */
  public function getUserInfo($uid, $field = '*'){
    return $this->field([$field])->find($uid);
  }

  /**
   * 获取完整代理链
   * @param    [type]                   $uid [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T10:42:44+080
   */
  public function getFullAgentPath($uid){
    return getUserFullPath($uid);
  }
  /**
   * 获取父级信息
   * @param    [type]                   $uid [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T10:56:45+080
   */
  function getUserByPid($uid, $where = []){
    return $this->where($where)->where('pid', $uid)->select();
  }

  /**
   * @author: slide
   * @param $where
   * @return false|\PDOStatement|string|\think\Collection
   */
  public function getUserList($where, $map){
    return $this->where($where)->where($map)->select();
  }

  /**
   * 查询会员下面四级
   * @author: slide
   * @return array
   */
  public function getUserChild($uid, $where = []){
    //第一级
    //dump($where);

    $child_result = getUserChild($uid, [], 1);
    $result = [];
    foreach ($child_result as $k => $v){
      foreach ($v as $key => $value){
        if($value['agent_type'] == $where['agent_type']){
          //dump($v);
          if(isset($where['keyword'])){
            $key = $where['keyword'];
            //dump($key);
            if(strpos($value['id'], $key)){
              $result[] = $value;
            }elseif(strpos($value['phone'], $key)){
              $result[] = $value;
            }elseif(strpos($value['nickname'], $key)){
              $result[] = $value;
            }else{
              $result[] = $value;
            }
          }else{
            $result[] = $value;
          }
        }
      }
    }
    return $result;
  }

  /**
   * 申请agent
   * @methods
   * @desc
   * @author slide
   *
   */
  public function agentApplyHandler($apply_id, $status, callable $callBack = NULL) {
    $res = Db::name('agent_apply')->where("id={$apply_id} AND status=0")->find();
    $flag = false;
    $user_info = Db::name('user')->field(['nickname','phone','agent_type','id','pid','bindwx','parent_agent','user_vb'])->find($res['user_id']);
    $infos=Db::name('user_weichat_info')->find($user_info['bindwx']);
    $uleve=Db::name('agent')->where('id',$user_info['agent_type'])->value('name');
    if(!$uleve){
      $uleve='vip会员';
    }
    $uleve2=Db::name('agent')->where('id',$res['agent_type'])->value('name');
    $template_taskprocessing = cache_data( 'site_config' )[ 'template_taskprocessing' ];
    if($status == 2) {
      $result = Db::name('agent_apply')->where("id={$apply_id}")->update(['status' => 2, 'updatetime' => time()]);

      if ($template_taskprocessing) {
        $wechat           = new Weichat();

        $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/user/index'), 'TASK_PROCESSING', ["任务处理通知 ","代理商等级升级通知","亲爱的用户，您的申请已被拒绝，详情请您咨询客服.","谢谢您的支持与厚爱!"],$user_info['id']);

        if ( $template_send_re ) {
          Log::write( '代理任务:模板消息发送成功', 'info');
        } else {
          Log::write( '代理任务:模板消息发送失败', 'info');
        }
      }
      call_user_func($callBack, $result);
      return;
    }

    $nickname = ($user_info['nickname'] != '' && !is_null($user_info['nickname'])) ? $user_info['nickname'] : (substr($user_info['phone'],0, 3)."*****".substr($user_info['phone'],-1, 3));
    if($res) {

      try {
        //分成
        $agent_info     = Db::name( 'agent' )->field( [ 'id', 'tc_proportion', 'name', 'free' ] )->select();
        $agent_info_key = convert_arr_key( $agent_info, 'id' );
        // 1、计算需要分成的总额
        $total_money_for_agent = (int) $res[ 'money' ]; #$agent_info_key[$res['agent_type']]['free'];
        L( '需要分成的代理' . $total_money_for_agent );

        // 确定用户金额
        // 同意申请
        /*if ( intval( $user_info[ 'user_vb' ] ) < intval( $total_money_for_agent ) ) {
          $result = Db::name( 'agent_apply' )->where( "id={$apply_id}" )->update( [ 'status' => 2, 'updatetime' => time() ] );

          if ( $template_taskprocessing ) {
            $wechat = new Weichat();

            $template_send_re = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/user/index' ), 'TASK_PROCESSING', [ "任务处理通知 ", "代理商等级升级通知", "亲爱的" . $nickname . "，您的申请已被拒绝，原因是您的账户余额不足，请充值后再试！详情请您咨询客服.", "谢谢您的支持与厚爱!" ], $user_info[ 'id' ] );

            if ( $template_send_re ) {
              Log::write( '代理任务:模板消息发送成功', 'info' );
            } else {
              Log::write( '代理任务:模板消息发送失败', 'info' );
            }
          }

          return $this->ajax( 4000, '用户余额不足' );
        }*/
        // 减去用户的钱
        //accountLog( $user_info[ 'id' ], $total_money_for_agent, 2, '升级成为' . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ] . '费用' );
        Db::startTrans();
        $first_agent = $agent_info_key[ 1 ][ 'tc_proportion' ] / 100;
        L( '省级比例' . $first_agent );
        $total_money_for_agent = $total_money_for_agent;
        $need_for_agent        = $total_money_for_agent * $first_agent;
        L( '需要分成的总金额' . $need_for_agent );

        // 2、按层级查询数据
        $parent_agent_user = getUserFullPath( $res[ 'user_id' ] );
        \Think\Log::write( '用户数据' . json_encode( $parent_agent_user ) );
        //循环进行分成
        $divided_total = 0;
        $message       = new \app\api\controller\Message();

        // 真正用来分成的比例记录，用于跳级分成
        $real_user_have = 0;

        foreach ( $parent_agent_user as $k => $v ) {
          L( 'key' . $k, 'info' );
          if ( !$v[ 'agent_type' ] ) {
            continue;
          }
          //if($v['agent_type'] == $parent_agent_user[$k - 1]['agent_type']) continue;
          $curr_agent = $agent_info_key[ $v[ 'agent_type' ] ][ 'tc_proportion' ] / 100;
          L( $v[ 'id' ] . '实际比例' . $curr_agent );
          //计算本级应该分成的比例
          if ( $k > 0 ) {
            L( '上一个会员的代理等级' . $parent_agent_user[ $k - 1 ][ 'agent_type' ] );
            $flag = $parent_agent_user[ $k - 1 ][ 'agent_type' ] != 0;
            L( $flag );
            if ( $parent_agent_user[ $k - 1 ][ 'agent_type' ] != 0 && ( $parent_agent_user[ $k - 1 ][ 'agent_type' ] <= $v[ 'agent_type' ] ) ) {
              continue;
            }
            L( '当前用户信息' . json_encode( $parent_agent_user[ $k - 1 ] ) );
            if ( $parent_agent_user[ $k - 1 ][ 'agent_type' ] != 0 ) {
              $pre_agent = $agent_info_key[ $parent_agent_user[ $k - 1 ][ 'agent_type' ] ][ 'tc_proportion' ] / 100;
            } else {
              $pre_agent = 0;
            }

            L( '上一个会员的比例' . $pre_agent );
            L( '当前会员的比例' . $curr_agent );
            L( '第一级用户比例' . $first_agent );
            // 如果 区域代理比例 - 上一个代理的比例 <= 小于当前比例
            if ( $curr_agent - $real_user_have < 0 ) {
              continue;
            }
            if ( $first_agent - $real_user_have <= ( $curr_agent - $real_user_have ) ) {
              L( '上一个实际分成的比例' . $real_user_have );
              $temp_agent_tc_proportion = $first_agent - $real_user_have; // 真实比例=省级代理的比例 - 上一个真实分成出去的比例
              $temp_agent_money         = $total_money_for_agent * $temp_agent_tc_proportion;
              $divided_total            += $temp_agent_money;
              L( '当前实际分成的比例' . $temp_agent_tc_proportion );
              $real_user_have = $temp_agent_tc_proportion; // 记录分成比例
              accountLog( $v[ 'id' ], $temp_agent_money, 1, '伙伴申请成为' . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ] . '分成' );
              divideNote( [ 'user_id' => $v[ 'id' ], 'order_id' => $res[ 'id' ], 'commission' => $temp_agent_tc_proportion, 'total_money' => $temp_agent_money, 'level' => isset( $v[ 'level' ] ) ? $v[ 'level' ] : 0, 'type' => 2 ] );
              L( $v[ 'id' ] . '当前比例' . $temp_agent_tc_proportion, 'info' );
              writeBusinessOrder( $v[ 'id' ], $temp_agent_money );
              break;
            } else {
              $temp_agent_tc_proportion = $curr_agent - $pre_agent;
              $temp_agent_money         = $total_money_for_agent * $temp_agent_tc_proportion;
              $real_user_have           = $temp_agent_tc_proportion;
              $divided_total            += $temp_agent_money;
              L( '微信比例' . $temp_agent_tc_proportion );
              L( '微信fencheg' . $temp_agent_money );
              accountLog( $v[ 'id' ], $temp_agent_money, 1, '伙伴申请成为' . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ] . '分成' );
              divideNote( [ 'user_id' => $v[ 'id' ], 'order_id' => $res[ 'id' ], 'commission' => $temp_agent_tc_proportion, 'total_money' => $temp_agent_money, 'level' => isset( $v[ 'level' ] ) ? $v[ 'level' ] : 0, 'type' => 2 ] );
              L( '当前比例' . $temp_agent_tc_proportion, 'info' );
              writeBusinessOrder( $v[ 'id' ], $temp_agent_money );
            }
          } else {
            // 往上第一级直接分满
            $real_user_have   = $curr_agent;
            $temp_agent_money = $total_money_for_agent * $curr_agent;
            $divided_total    += $temp_agent_money;
            accountLog( $v[ 'id' ], $temp_agent_money, 1, '下级申请成为' . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ] . '分成' );
            divideNote( [ 'user_id' => $v[ 'id' ], 'order_id' => $res[ 'id' ], 'commission' => $curr_agent, 'total_money' => $temp_agent_money, 'level' => isset( $v[ 'level' ] ) ? $v[ 'level' ] : 0, 'type' => 2 ] );
            L( '当前比例' . $curr_agent, 'info' );
            writeBusinessOrder( $v[ 'id' ], $temp_agent_money );
          }

          // 享受下级直接收入的5%
          Motuijian::diectReword($v['pid'], $temp_agent_money, $v, $res);

          // 每一个上级发送一条消息
          $message->sendMsg( "您有有一名团队好友成功申请成为" . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ], '您的团队伙伴[' . $nickname . ']在' . date( 'Y-m-d H:s' ) . '成功通过代理审核，成为了' . $agent_info_key[ $res[ 'agent_type' ] ][ 'name' ], -1, $v[ 'id' ], 3, false, '', false, false );
        }

        //所有人都分完，多余进公司账户
        $supplus_money  = $total_money_for_agent - $divided_total;
        $company_accout = Db::name( 'user' )->where( 'is_employ_agent', 1 )->find();
        if ( $company_accout && $supplus_money ) {
          accountLog( $company_accout[ 'id' ], $supplus_money, 1, '分成剩下的金额转入公司账户' );
          divideNote( [ 'user_id' => $company_accout[ 'id' ], 'order_id' => 0, 'commission' => 0, 'total_money' => $supplus_money, 'level' => 0, 'type' => 2, 'user_info' => json_encode( $user_info ), 'order_info' => json_encode( $res ) ] );
        } else {
          //转入冻结表
          change_over_to_not_divide( 1, $supplus_money );
        }

        $flag = true;
        if ( $res[ 'agent_type' ] == 1 ) {
          //修改当前申请的用户下级的parent_agent
          $apply_user_child = getUserChild( $res[ 'user_id' ] );
          L( '下级会员' . json_encode( $apply_user_child ) );
          if ( !empty( $apply_user_child ) && isset( $apply_user_child ) && !empty( $apply_user_child ) ) {
            $ids = '';
            foreach ( $apply_user_child as $n => $m ) {
              if ( !empty( $m ) ) {
                $temp_ids = implode( ',', array_keys( convert_arr_key( $m, 'id' ) ) );
                $ids      .= $temp_ids . ",";
              }
            }
            L( '下级会员id' . $ids );
            if ( $ids != '' ) {
              //修改下级的parent_id
              $ids  = trim( $ids, ',' );
              $flag = Db::name( 'user' )->where( "id IN ({$ids})" )->update( [ 'parent_agent' => $res[ 'user_id' ] ] );
            }
          }
        }
        //更改状态
        $result = Db::name( 'agent_apply' )->where( "id={$apply_id}" )->update( [ 'status' => 1, 'updatetime' => time(), 'is_distribut' => 1, 'total_distribue' => $need_for_agent, 'actual_distribut' => $divided_total ] );

        $user_res = Db::name( 'user' )->where( 'id', $res[ 'user_id' ] )->setField( 'agent_type', $res[ 'agent_type' ] );

        // 默认下单

        Db::commit();
        $params = [ 'apply_res' => $res, 'userInfo' => $user_res ];
        Hook::call( 'Agent', 'confirm', $params);
        if ( $template_taskprocessing ) {
          $wechat = new Weichat();

         // $template_send_re   = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/user/index' ), 'TASK_PROCESSING', [ "任务处理通知 ", "代理商等级升级通知", "【代理商等级升级通知】亲爱的" . $infos[ 'nickname' ] . "您已于" . date( 'Y-m-d H:i:s', time() ) . "由" . $uleve . "升级为" . $uleve2, "谢谢您的支持与厚爱" ], $user_info[ 'id' ] );
          $template_send_res  = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/user/index' ), 'TASK_PROCESSING', [ "任务处理通知 ", "团队伙伴付款通知", "【团队伙伴付款通知】您的伙伴" . $infos[ 'nickname' ] . "已于" . date( 'Y-m-d H:i:s', time() ) . "购买产品" . $uleve2, "谢谢您的支持与厚爱" ], $user_info[ 'pid' ] );
          // $template_send_ress = $wechat->sendTemplateMsg( WEB_DOMAIN . url( 'home/user/index' ), 'TASK_PROCESSING', [ "任务处理通知 ", "团队伙伴付款通知", "【团队伙伴付款通知】您的伙伴" . $infos[ 'nickname' ] . "已于" . date( 'Y-m-d H:i:s', time() ) . "购买产品" . $uleve2, "谢谢您的支持与厚爱" ], $user_info[ 'parent_agent' ] );

          if ( $template_send_res ) {
            Log::write( '代理任务:模板消息发送成功', 'info' );
          } else {
            Log::write( '代理任务:模板消息发送失败', 'info' );
          }
        }
       // adminLog( '操作代理申请成功' );
        if ( $flag && $result && $user_res ) {
          $flag = true;
        } else {
          $flag = false;
        }

      } catch ( \Exception $e ) {
        Db::rollback();
        L( '错误' . $e );
        $flag = false;
      }
    }
    call_user_func($callBack, $flag);
  }
}
