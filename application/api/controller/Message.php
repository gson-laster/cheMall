<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25 0025
 * Time: 下午 2:44
 */

namespace app\api\controller;
use think\Db;
use think\Session;
use think\Config;
use think\Controller;
use think\Log;
use GatewayClient\Gateway;
use app\home\controller\Weichat;
use app\common\model\Message as MessageModel;
class Message extends Base {
  protected $msgMdl;

  protected function _initialize () {
    parent::_initialize();
    $this->msgMdl = new MessageModel();
  }
  /**
   * 给指定的用户发送消息
   *
   * @param    string  $title [消息标题|普通消息不用标题]
   * @param    [type]                   $msg          [消息内容]
   * @param    [type]                   $consignee_id [收件人id]
   * @param    integer $type  [消息类型默认为普通消息 1普通消息2、订单消息3、系统消息]
   * @param    [int]   $minitype 1 文本 2 图片
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-16T10:45:04+080
   */
  public function sendMsg ( $title = '', $msg, $sender_id = '', $consignee_id = '', $type = 1,$minitype=1, $config='', $send = true, $is_just_send=false ) {
    $send_id = $sender_id ? $sender_id : Session::get( 'qt_userId' );
    if($send_id == $consignee_id) return $this->ajax(4000, '不能发消息给自己');
    //存数据库
    // 启动事务
    Db::startTrans();
    try {
      $message_data = [ 'title' => $title, 'content' => $msg, 'sender' => $send_id,'config'=>$config, 'type' => $type, 'minitype'=>$minitype ];

      $this->msgMdl->data( $message_data, true );

      $msg_res = $this->msgMdl->allowField( true )->isUpdate(false)->save();
      L( '插入消息表结果' . json_encode( $msg_res ), 'info' );
      if(!$is_just_send){
        $message_consignee_data = [ 'message_id' => $this->msgMdl->id, 'consignee_id' => $consignee_id, 'sender_id' => $send_id, 'message_status' => 0, 'type'=>$type,'create_time' => time() ];
        $res = Db::name( 'message_consignee' )->insert( $message_consignee_data );
        L( '插入收件表结果' . $res, 'info' );
      }

      // 提交事务
      Db::commit();
      //是否在线
      Gateway::$registerAddress = Config::get( 'Message_Register_Address' );
      $client_id = Gateway::getClientIdByUid($consignee_id);
      L('客户端ID'.json_encode($client_id));
      // 发送消息模板消息给客服
      $wechat           = new Weichat();
      if($consignee_id == -2){

        $nickname_asp = model('user')->alias('u')->field(['u.nickname','wx.nickname as wx_nickname'])->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx')->where('u.id', $send_id)->find();
        $nickname_asp_str = $nickname_asp['nickname'] == '' ? $nickname_asp['wx_nickname'] : $nickname_asp['nickname'];
        $kefu_uid = getUserInfoBywhere(['phone'=> Config::get('message_kefu_account')]);
        if($kefu_uid) {
          $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
            '亲爱的 客服专员 来自【'.$nickname_asp_str.'】新的咨询消息需要回复!','回复咨询消息','待办','请尽快处理！'], $kefu_uid['id']);
          L('反悔哦'.json_encode($template_send_kefu_res));
          if ( $template_send_kefu_res ) {
            Log::write( '发送用户:' .$sender_id . '模板消息发送成功', 'info' );
          } else {
            Log::write( '发送用户:' . $sender_id . '模板消息发送失败', 'info' );
          }
        }
      }
      if(Gateway::isUidOnline($consignee_id)){
        $send && $this->sendMsgToClient($consignee_id,  $this->msgMdl->id, $msg, $send_id, $minitype );
      }else{
          $template_counsel=cache_data( 'site_config' )[ 'template_counsel' ];

          if ($template_counsel) {
              $msg = $minitype == 2 ? '图片消息' : $msg;
              $template_send_re = $wechat->sendTemplateMsg(WEB_DOMAIN . url( 'home/message/dialogList')."?consignee_id=".$send_id ,'COUNSEL',["您好，您有一条消息未读 ",Config::get('SMS_SIGN'),$msg,"点击了解详情"],$consignee_id);
          if ( $template_send_re ) {
                  Log::write( '咨询消息:模板消息发送成功', 'info');
              } else {
                  Log::write( '咨询消息:模板消息发送失败', 'info');
              }
          }
      }

      return $this->ajax( 2000, '发送成功', '', $message_consignee_data );
    } catch ( \Exception $e ) {
      // 回滚事务
      L( '错误消息' . $e, 'info' );
      Db::rollback();

      return $this->ajax( 4000, '发送失败' );
    }

  }

  /**
   * 通过消息系统给客户端发送消息
   * @author: slide
   *
   * @param $consignee_id
   * @param $msg_id
   * @param $msg
   * @param $sender_id
   * @param $minitype
   *
   */
  public function sendMsgToClient ( $consignee_id, $msg_id, $msg, $sender_id, $minitype ) {
    // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
    $sender_name = $sender_id == -2 ? '
    ' : session( 'userInfo' )[ 'nickname' ];
    $sender_img = $sender_id == -2 ? '/thems/default/static/img/message_index_08.png' : session( 'userInfo' )[ 'userimg' ];
    Gateway::$registerAddress = Config::get( 'Message_Register_Address' );
    $messages                 = [ 'type'      => 'msg', 'msg_id' => $msg_id, 'content' => htmlspecialchars( $msg ),  //转义
                                  'sender_id' => $sender_id, 'sender_img' => $sender_img, 'sender_nickname' => $sender_name,'minitype'=>$minitype,'sender_phone' => session( 'userInfo' )[ 'phone' ], 'time' => time() ];

    // 向任意uid的网站页面发送数据
    $online      = Gateway::getClientIdByUid( Session::get( 'qt_userId' ) );
    $send_result = Gateway::sendToUid( $consignee_id, json_encode( $messages ) );
    L( '发送结果' . json_encode( $send_result ), 'info' );
    L( '在线' . json_encode( $online ), 'info' );
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
  public function dialogList ( $consignee_id, $sender_id = -2, $pageSize = 10, $page = 1 ) {
    if ( $consignee_id ) {
      $consignee_ids =  "'".$sender_id."','" . $consignee_id ."'";
      $consignee_msg = Db::name( 'message_consignee' )->alias( 'mc' )->field( [ 'mc.*','m.content','m.minitype', 'm.create_time as send_time' ] )->join( "__MESSAGE__ m", "m.id = mc.message_id" )->where( "consignee_id IN ({$consignee_ids}) AND sender_id IN({$consignee_ids}) AND consignee_id <> sender_id" )->order( 'create_time desc' )->paginate( $pageSize, false, [ 'page' => $page ] );
      $userInfo = Db::name('user')->alias('u')->field(['u.id','u.userimg','u.phone','u.nickname','wx.nickname as wx_nickname','wx.headimgurl'])->join("__USER_WEICHAT_INFO__ wx",'wx.id=u.bindwx','LEFT')->find($consignee_id);
      // $data = array_merge($consignee_msg->toArray(), ['userInfo'=>$userInfo]);
      //dump(Db::name( 'message_consignee' )->getLastSql());
      return $this->ajax( 2000, '获取成功', '',  ['msg_data'=>$consignee_msg, 'userInfo'=>$userInfo]);
    } else {
      $this->error( '请选择联系人，再查看' );
    }
  }
}
