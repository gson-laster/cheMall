<?php
use think\Db;

/**
 * 获取父级信息
 * @param    [type]                   $uid [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-13T10:56:45+080
 */
function getUserParent($uid, $where = []){
  return Db::name('user')->where($where)->find($uid);
}

/**
 * 获取父级信息
 * @param    [type]                   $uid [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-13T10:56:45+080
 */
function getUserByPid($uid, $where = []){
  return Db::name('user')->where($where)->where('pid', $uid)->select();
}

/**
 * 获取所有的父级
 * @param    [type]                   $uid [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-13T10:57:21+080
 */
function getUserFullPath($uid,$need_num = 5){
  static $user_res = [];
  static $num = 0;
  //dump($uid);
  $result = getUserParent($uid);
  if($result['pid'] != 0 && $num < $need_num && $result['agent_type'] != 1){
    $temp_arr = getUserParent($result['pid']);
    $user_res[] =$temp_arr;
    $num ++;
    getUserFullPath($result['pid']);
  }
  return $user_res;
}

/**
 * 获取会员列表
 * @author: slide
 * @param $where
 * @param $map
 * @return mixed
 *
 */
function getUserList($where, $map = []){
  return Db::name('user')->alias('u')->field(["u.*",'wx.nickname as wx_nickname','wx.headimgurl'])->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','LEFT')->where($where)->where($map)->order('u.createtime desc')->select();
}

/**
 * 根据唯一字段查询用户信息（id|phone|emali）
 * @author: slide
 *
 * @param $field
 * @param $value
 *
 */
function getUserInfoBywhere($where, $field= "*"){
  return Db::name('user')->field($field)->where($where)->select();
}

function getUserInfoBywhere2($where, $field= "*"){
  return Db::name('user')->field($field)->where($where)->find();
}
/**
 * 查询用户信息
 * @methods
 * @desc
 * @author slide
 *
 */
function getAgentChildUserListByAgentId($user_ids, $s_num = 4, $level = 1){
  $user_data = [];
  if($user_ids != '' && $s_num >= $level) {
    $user_child_res = getUserInfoBywhere("pid IN ({$user_ids})", 'id,pid,agent_type');
    array_push($user_data, $user_child_res);

    if(empty($user_child_res)) return zuHeDataForGetAgnetChild($user_data);
    $user_ids_1 = implode(',', array_keys(convert_arr_key($user_child_res, 'id')));
    $user_child_res_1 = getUserInfoBywhere("pid IN ({$user_ids_1})", 'id,pid,agent_type');
    array_push($user_data, $user_child_res);

    if( empty($user_child_res_1)) return zuHeDataForGetAgnetChild($user_data);

    $user_ids_2 = implode(',', array_keys(convert_arr_key($user_child_res_1, 'id')));
    $user_child_res_2 = getUserInfoBywhere("pid IN ({$user_ids_2})", 'id,pid,agent_type');
    array_push($user_data, $user_child_res_2);

    if( empty($user_child_res_2)) return zuHeDataForGetAgnetChild($user_data);

    $user_ids_3 = implode(',', array_keys(convert_arr_key($user_child_res_2, 'id')));
    $user_child_res_3 = getUserInfoBywhere("pid IN ({$user_ids_3})", 'id,pid,agent_type');
    array_push($user_data, $user_child_res_3);

  }
  L('执行到了这里');
  $result = zuHeDataForGetAgnetChild($user_data);
  L('返回结果'.var_export($result));
  return $result;
}

function zuHeDataForGetAgnetChild($user_data){
  $result = [];
  foreach ($user_data as $k => $v) {
    if(is_array($v) && !empty($v)){

      foreach ($v as $key => $val) {
        $result[] = $val;
      }
    }
  }
  $result = convert_arr_key($result, 'id');
  return $result;
}

/**
 * 获取会员的下级
 * @author: slide
 *
 */
function getUserChild ($user_child_ids, $where = [], $s_num = 4, $level = 1) {
  $user_res = [];
  //dump($user_child_ids);
  if ($user_child_ids != '' && $level <= $s_num){
    $user_child_res = getUserList("pid IN ({$user_child_ids})", $where);
    // dump($user_child_res);
    $user_res[] = $user_child_res;
    //    array_push($user_res, $user_child_res);
    //    foreach ($user_child_res as $k => $v){
    //      if($v['agent_type'] == 1){
    //        unset($user_child_res[$k]);
    //      }
    //    }
    $temp_child_ids = implode(',', array_keys(convert_arr_key($user_child_res, 'id')));
    //dump($temp_child_ids);
    $level ++;
    getUserChild($temp_child_ids, $where, $s_num, $level);
  }
  //dump($user_res);exit;
  return $user_res;
}

/**
 * @author: slide
 * @param $fortime
 * @return false|string
 */
function dateaway($fortime)
{

  $today = strtotime(date('Y/m/d'));
  $yestoday = strtotime(date("Y/m/d", strtotime("-1 day")));

  if($fortime - $today > 0 && $fortime - $today <= 86400){
    $return = date('H:i', $fortime);
  }elseif($fortime - $yestoday >0 && $fortime - $yestoday <= 86400){
    $return = '昨天';
  }else{
    $return = date('Y/m/d', $fortime);
  }
  return $return;
}

/**
 * 记录失败的任务
 * @author: slide
 * @param $data array 相关数据
 */
function recordTaskFaile($run_time, $url){
  $data['time'] = time();
  $data['run_time'] =$run_time;
  $data['url'] = $url;
  Db::name('task')->insert($data);
}

/**
 * 任务记录
 * @author: slide
 * @param $ctrl   执行控制器
 * @param $status 执行完的状态
 * @param $type   类型
 *
 */
function writeTaskNote($ctrl, $status, $note){
  $data['task_ctrl'] = $ctrl;
  $data['run_time'] = time();
  $data['status'] = $status;
  $data['note'] = $note;

  Db::name('task_note')->insert($data);
}

/**
 * 记录招商分成
 * @author: slide
 *
 */
function writeBusinessOrder($user_id, $money){
  $data['user_id'] = $user_id;
  $data['money']   = $money;
  $data['createtime'] = time();

  Db::name('business_order')->insert($data);
}

/**
 * 省级代理对省级代理分成 5%
 * @author: slide
 * @param $agent_data
 *
 */
function agent2anget($agent_data, $time_where){
  $percent = think\Config::get('every_month_divide');
  try{
    foreach ( $agent_data as $k => $v ) {

      if ( isset($v['child']) && !empty($v['child']) ) {
        // 计算分成
        // 获取招商分成
        $total_child_business_money = 0;
        foreach ( $v[ 'child' ] as $key => $value ) {
          //L('value'. json_encode($value));
          $parent_agent               = $value[ 'id' ];
          $temp_money                 = Db::name( 'divide_note' )->where( 'user_id', $parent_agent )->where( $time_where )->sum( 'total_money' );
          L('下级代理收入'. json_encode($temp_money, true));
          $total_child_business_money += ( $temp_money * 1 ) * $percent;
          //accountLog( $parent_agent, (($temp_money * 1) * $percent), 2, date( 'Y-m-d', time() ) . "向上级省级代理贡献招商收入" . ( $percent * 100 ) . "%，分成收入" );
          //sendSiteMsg(-1, $parent_agent, '', date( 'Y-m-d', time() ) . "向上级省级代理贡献招商收入" . ( $percent * 100 ) . "%分成",3, false);
        }
        // 分成给自己
        $should_get_money = $total_child_business_money;
        L('代理'.$v['id'].'收入'.$should_get_money);
        //自己增加
        if($should_get_money){
          accountLog( $v[ 'id' ], $should_get_money, 1, date( 'Y-m-d', time() ) . "下级招商收入" . ( $percent * 100 ) . "%，分成收入" );
          writeCompanyDivide($v['id'], $should_get_money); // 公司记录
          sendSiteMsg(-1, $v[ 'id' ], '', date( 'Y-m-d', time() ) . "获得合伙人招商金额" . ( $percent * 100 ) . "%",3, false,false,false);
        }
        // 分完继续递归往下
       // agent2anget($v, $time_where);
      }
    }
    return true;
  }catch (\Exception $e){
    L('错误'.$e);
    return false;
  }
}

/**
 * 站内信发送
 * @author: slide
 * @param        $sender_id         发送人
 * @param        $consignee_id      收件人
 * @param string $title             短息标题(系统消息,订单消息)
 * @param string $content           短息内容
 * @param int    $type              短信类型(1普通消息2、订单消息(-1)3、系统消息(-1))
 * @param bool   $is_send           是否在线发送
 */
function sendSiteMsg($sender_id='', $consignee_id, $title="", $content='', $type=1,$minitype=1, $config='', $is_send = true, $is_just_send=false){
  $msgCtrl = new \app\api\controller\Message();
  $msgCtrl->sendMsg($title, $content, $sender_id, $consignee_id, $type, $minitype, $config, $send = $is_send, $is_just_send);
}

/**
 * 用户获取公告信息
 * @author: slide
 *
 * @param $user_id
 *
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 *
 */
function userGetNotice($user_id){
  // 公告
  $notice_msg = Db::name('message')->where('type', 4)->select();
  // $notice_arr = array_keys(convert_arr_key($notice_msg, 'id'));

  $user_consignee_msg = Db::name('message_consignee')->where(['consignee_id'=>$user_id, 'type'=>4])->select();
  $user_consignee_arr = array_keys(convert_arr_key($user_consignee_msg, 'message_id'));
  // 未读公告消息
  $no_read_arr = [];
  foreach ($notice_msg as $k => $v){
    if(!in_array($v['id'], $user_consignee_arr)){
      array_push($no_read_arr, $v);
    }
  }

  // 填入收件表
  foreach ($no_read_arr as $key => $value){
    $message_consignee_data = [ 'message_id' => $value['id'], 'consignee_id' => $user_id, 'sender_id' => $value['sender'], 'message_status' => 0, 'type'=>$value['type'],'create_time' => time() ];
    $res = Db::name( 'message_consignee' )->insert( $message_consignee_data );
    L( '插入收件表结果' . $res, 'info' );
  }
}

/**
 * 写入从公司分成5%
 * @author: slide
 * @param $user_id
 * @param $money
 *
 */
function writeCompanyDivide($user_id, $money, $type=1){
  $data['user_id'] = $user_id;
  $data['money'] = $money;
  $data['type'] = $type;
  $data['createtime'] = time();

  Db::name('agent_company_divide')->insert($data);
}

/**
 * 记录商品分成
 * @author: slide
 *
 * @param $user_id
 * @param $order_id
 * @param $money
 *
 */
function writeGoodsDivide($user_id, $order_id, $money){
  $data['user_id'] = $user_id;
  $data['order_id'] = $order_id;
  $data['money']  = $money;
  $data['createtime'] = time();

  Db::name('goods_divide')->insert($data);
}

/**
 * 省级代理
 * @author: slide
 * @param $agent_data
 *
 */
function agent_child ($agent_data) {
  $user_data = [];
  $agent_data = convert_arr_key($agent_data, 'id');
  $keys = array_keys($agent_data);
  foreach ($agent_data as $k => $v){
    if(in_array($v['pid'], $keys)){
      // 如果pid在id中
      $user_data[$v['pid']] = $agent_data[$v['pid']];
      $user_data[$v['pid']]['child'][] = $v;
    }else{
      $user_data[$k] = $v;
    }
  }
  return $user_data;
}

/**
 * 重新从消息列表中拿出用户ID、
 */
function reset_cobntact($data){
	if(!is_array($data)){
		return '';
	}
	$ids = '';
	foreach($data as $k => $v){
		if($v['sender_id'] != -2){
			$ids .= $v['sender_id'].',';
		}else if($v['consignee_id'] !=-2){
			$ids .= $v['consignee_id'].',';
		}
	}

	return trim($ids, ',');
}

/**
 * 获取会员的下级
 * @author: slide
 *
 */
function getChildrenUser ($user_child_ids, $where = [], $s_num = 4, $level = 0) {
  static $user_res = [];

  if ($user_child_ids != '' && $level <= $s_num){
    $user_child_res = getUserList("pid IN ($user_child_ids)", $where);

    $user_res[] = $user_child_res;

    $temp_child_ids = implode(',', array_keys(convert_arr_key($user_child_res, 'id')));

    getUserChild($temp_child_ids, $where, $s_num, $level ++);
  }
  return $user_res;
}

/***
 *
 * 用户中心货币变更类
 * @param string $userid   用户id
 * @param string $change_remake 变动目录
 * @param string $change_num   变动金额
 * @param string $change_symbol 变动符号 +, -
 * @param string $change_type  变动类型 1-积分  2-赠品券 3-订货额
 * @return bool
 */
function  ex_balancechange($userid='',$change_remake='',$change_num=0,$change_symbol='',$change_type=''){
  /* 插入帐户变动记录 */
  $account_log = array(
    'change_remake'=>$change_remake,
    'change_num'=>$change_num,
    'updatetime'=>time(),
    'change_symbol'=>$change_symbol,
    'change_type'=>$change_type,
    'userid'=>$userid
  );
  if($change_symbol=='-'){
    $change_num=$change_num * -1;
  }
  try{
    Db::startTrans();
    Db::name('balance_change')->insert($account_log);
    if($change_type=='1') {
      Db::name('user')->where('id', $userid)->setInc('record', $change_num);
    }else if($change_type=='2'){
      Db::name('user')->where('id',$userid)->setInc('trading_stamp',$change_num);
    }else{
      Db::name('user')->where('id',$userid)->setInc('order_amount',$change_num);
    }

    Db::commit();
    return true;

  }catch (\Exception $e){
    L('用户中心货币更改失败'.$e);
    Db::rollback();
    return false;

  }
}

/**
 * 订货积分变更
 */
function  ex_intergral($id,$change_num){
    try{
        Db::startTrans();
        Db::name('integral')->where('id', $id)->setDec('integral',$change_num);
        Db::commit();
        return true;

    }catch (\Exception $e){
        L('积分释放失败'.$e);
        Db::rollback();
        return false;

    }



}
?>
