<?php
namespace app\home\controller;

use think\Config;
use think\Db;
use think\Session;

use app\common\model\Message as MessageModel;
use GatewayClient\Gateway;

/**
 * 消息处理
 */
class Message extends Validate {
  protected $msgMdl;

  protected function _initialize () {
    parent::_initialize();
    ///dump($_SESSION);
    $this->msgMdl = new MessageModel();
  }

  /**
   * 获取消息列表
   *
   * @param    int $type   [消息类型，有则显示类型消息，没有这普通消息]
   * @param    int $status [消息状态，默认显示全部]
   *
   * @return   [type]                          [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T09:53:43+080
   */
  public function getUserMsgList ($pageSize = 10, $page = 1 ) {

    // 订单消息最后一条
    $order_msg_wh['mc.type'] = 2;
    $order_msg_res = $this->getMessage( Session::get( 'qt_userId' ), $order_msg_wh,1 );

    //系统消息最后一条
    $sys_msg_wh['mc.type'] = 3;
    $sys_msg_res = $this->getMessage( Session::get( 'qt_userId' ), $sys_msg_wh, 1 );
    //dump($sys_msg_res);
    //dump($order_msg_res);exit;
    // 根据发送者获取消息列表
    $msg = $this->groupDialogList( Session::get( 'qt_userId' ));
    //dump($msg);
    $service = Db::name('agent_employee')->where(['agent_id'=>Session::get('userInfo')['parent_agent'],'is_service'=>1])->find();

    // 公告
    $sys_msg_wh['mc.type'] = 4;
    $notice_msg = $this->getMessage( Session::get( 'qt_userId' ), $sys_msg_wh, 1 );
//    dump($service);
    if($service){
      $server_id = $service['msg_uid'];
    }else{
      $server_id = '-2';
    }
    return $this->fetch( 'list', [ 'list' => $msg, 'order_msg' => $order_msg_res, 'sys_msg' => $sys_msg_res, 'sever_uid'=> $service, 'notice_msg'=>$notice_msg] );
  }

  /**
   * 获取收件的消息
   *
   * @param    [type]                   $user_id [收件人id]
   * @param    [type]                   $where   [额外查询条件]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T09:57:15+080
   */
  public function getMessage ( $user_id, $where = [], $limit = '', $group = '', $order = 'create_time DESC') {
    $res = Db::name( 'message_consignee' )->alias('mc')->field(['mc.*','u.nickname','u.userimg','u.phone','m.content', 'm.minitype','m.create_time as send_time'])->join("__USER__ u", 'u.id=mc.sender_id', 'LEFT')->join('__MESSAGE__ m', 'm.id=mc.message_id', 'LEFT')->where( 'mc.consignee_id', $user_id )->where( $where )->group($group)->order($order)->limit($limit)->select();
     //dump(Db::name('message_consignee')->getLastSql());
    return $res;
  }

  /**
   * 获取分组信息
   * @param    [type]                   $consignee_id [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-18T14:22:04+080
   */
  public function groupDialogList($consignee_id){
//    dump(111);
    $sql = "SELECT `a`.*, `u`.`nickname`,`u`.`userimg`,`u`.`phone`,`u`.`bindwx`,`m`.`minitype`,`m`.`content`,m.create_time AS send_time
    FROM
    	(
    		SELECT
    			*,consignee_id AS talkAboutId
    		FROM
    			ty_message_consignee
    		WHERE
    			sender_id = {$consignee_id}
    		UNION ALL
    			SELECT
    				*,sender_id AS talkAboutId
    			FROM
    				ty_message_consignee
    			WHERE
    				consignee_id = {$consignee_id}
    	) a
    LEFT JOIN `ty_user` `u` ON
    case a.sender_id
    WHEN {$consignee_id} then `u`.`id` = `a`.`consignee_id`
    ELSE `u`.`id` = `a`.`sender_id` END
    
    LEFT JOIN `ty_message` `m` ON `m`.`id` = `a`.`message_id`
    WHERE
    	a.create_time = (
    		SELECT
    			max(create_time)
    		FROM
    			(
    				SELECT
    					*,consignee_id AS talkAboutId
    				FROM
    					ty_message_consignee
    				WHERE
    					sender_id = {$consignee_id}
    				UNION ALL
    					SELECT
    						*,sender_id AS talkAboutId
    					FROM
    						ty_message_consignee
    					WHERE
    						consignee_id = {$consignee_id}
    			) b
    		WHERE
    			b.talkAboutId = a.talkAboutId
    	) AND a.type=1 GROUP BY a.talkAboutId ORDER BY a.create_time DESC;";
    $res = Db::query($sql);
    $uids_arr = array_keys(convert_arr_key($res, 'talkAboutId'));
    $uids = '';
    foreach($uids_arr as $k => $v){
    	$uids .= "'".$v."',";
    }

	$uids = trim($uids, ',');

    //dump($uids);
    $users_info_key_arr =[];
    if($uids != ''){

      $users_info = Db::name('user')->field(['id','nickname','bindwx','userimg'])->where("id IN ({$uids})")->select();
      $users_info_key_arr = convert_arr_key($users_info, 'id');

      $bindwxs = implode(',',array_keys(convert_arr_key($users_info, 'bindwx')));
      if($bindwxs != ''){
        $wx_info = Db::name('user_weichat_info')->where("id IN ($bindwxs)")->select();
        $wx_info_key_arr = convert_arr_key($wx_info,'id');
        foreach ($res as $k => &$v){
          if($v['talkAboutId'] <= 0) continue;
          if($v['nickname'] == ''){
            if($users_info_key_arr[$v['talkAboutId']]['bindwx'] != 0){
              $v['nickname'] = $wx_info_key_arr[$users_info_key_arr[$v['talkAboutId']]['bindwx']]['nickname'];
            }
          }
          if($v['userimg'] == ''){
            if($users_info_key_arr[$v['talkAboutId']]['bindwx'] != 0){
              $v['userimg'] = $wx_info_key_arr[$users_info_key_arr[$v['talkAboutId']]['bindwx']]['headimgurl'];
            }
          }
        }
      }
    }
    //dump($res);
    return $res;
  }

  /**
   * 把消息client_id与uid绑定
   *
   * @param    [type]                   $client_id [消息客户端id]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T10:11:23+080
   */
  public function bindUid ( $client_id ) {
    Gateway::$registerAddress = Config::get( 'Message_Register_Address' );
    // 假设用户已经登录，用户uid和群组id在session中
    $uid = session( 'qt_userId' );
    // client_id与uid绑定
    $res = Gateway::bindUid( $client_id, session( 'qt_userId' ) );
    if ( $res ) {
      return $this->ajax( 2000, '进入消息服务器成功' );
    } else {
      return $this->ajax( 4000, '进入消息服务器失败，请刷新重试' );
    }
  }


  /**
   * 读消息
   *
   * @param    [type]                   $msg_id [消息id]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T11:42:22+080
   */
  public function readMsg ( $msg_id ) {
    if ( $msg_id ) {
    	$res =  Db::name( 'message_consignee' )->where( "message_id IN ({$msg_id})" )->update( [ 'message_status' => 1 ] );
    	//dump(Db::name('message_consignee')->getLastSql());
      if ( $res ) {
        $this->success( '读取消息成功' );
      } else {
        $this->error( '读取失败' );
      }
    } else {
      $this->error( '没有这样的消息' );
    }
  }

  /**
   * 对话列表
   * @param    [type]                   $consignee_id [对话人id]
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T14:18:59+080
   */
  public function dialogList ( $consignee_id, $pageSize = 10, $page = 1 ) {
    if ( $consignee_id ) {
      $consignee_ids = "'".Session::get( 'qt_userId' ) . "','" . $consignee_id."'";
      $consignee_msg = Db::name( 'message_consignee' )->alias( 'mc' )->field( [ 'mc.*', 'u.nickname', 'u.phone', 'u.userimg','u.bindwx', 'm.content','m.minitype', 'm.create_time as send_time' ] )->join( '__USER__ u', 'u.id = mc.sender_id' )->join( "__MESSAGE__ m", "m.id = mc.message_id" )->where( "consignee_id IN ({$consignee_ids}) AND sender_id IN({$consignee_ids}) AND consignee_id <> sender_id" )->order( 'create_time desc' )->paginate( $pageSize, false, [ 'page' => $page ] );
    //  dump(Db::name( 'message_consignee' )->getLastSql());
      $wx_ids = array();
      foreach ($consignee_msg as $k => $v){
        if($v['bindwx'] != 0){
          array_push($wx_ids, $v['bindwx']);
        }
      }
      $wx_ids = array_unique($wx_ids);
      $wx_ids = implode(',',$wx_ids);
      $wx_info_key_arr = [];
      if($wx_ids != ''){
        $wx_info = Db::name('user_weichat_info')->where("id IN ($wx_ids)")->select();
        $wx_info_key_arr = convert_arr_key($wx_info, 'id');
      }

      //dump($wx_info_key_arr);
      //dump($wx_info_key_arr);
      $user_vb = model('user')->where('id', Session::get('userInfo')['id'])->value('user_vb');
      if ( $this->request->isAjax() ) {
        return $this->ajax( 2000, '获取成功', '', ['msg_data'=>$consignee_msg, 'wx_info'=>$wx_info_key_arr]);
      } else {
        $this->assign('user_vb', $user_vb);
        return $this->fetch();
      }
    } else {
      $this->error( '请选择联系人，再查看' );
    }
  }

  /**
   * 特殊消息列表
   * @author: slide
   * @param     $type  2/3    2订单消息  3 系统消息
   * @param int $pagesize
   * @param int $page
   *
   */
  public function specialMsg($type, $pagesize = 10, $page = 1){
    $msg_arr = Db::name('message_consignee')->alias('mc')->field(['mc.*','m.title','m.content','m.create_time as send_time'])->join('__MESSAGE__ m', 'm.id=mc.message_id','left')->where(["consignee_id"=>Session::get('qt_userId'), 'mc.type'=>$type])->order('mc.create_time desc')->paginate($pagesize, false, ['page' => $page]);

    if($this->request->isAjax()){
      return $this->ajax(2000, '查询成功', $msg_arr);
    }else{
      $this->assign('title', $type == 2 ? '订单通知':'系统通知');
      return $this->fetch();
    }
  }

  /**
   * 公告详情
   * @author: slide
   *
   * @param $id
   *
   */
  public function getNoticeDetail($id){

    $res = Db::name('notice')->find($id);
    if(!$res){
      return $this->error('没有这样的公告消息');
    }
    return $this->fetch('notice', ['detail' => $res]);
  }
}

?>
