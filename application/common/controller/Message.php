<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/26 0026
 * Time: 上午 9:40
 */

namespace app\common\controller;

use app\api\controller\Sms;
use app\home\controller\Weichat;
use app\common\model\Message as MessageMdl;

/**
 * 快捷调用消息封装
 * Class Message
 *
 * @package app\common\controller
 */
class Message {

  protected static $wechatMdl = null;  // 微信实例
  protected static $smsMdl = null;     // 短息实例
  protected static $msgMdl = null;     // 站内消息实例

  /**
   * 获取微信实例
   * @methods
   * @desc
   * @author slide
   * @return Weichat|null
   *
   */
  private static function _getWechatMdl(){
    if(is_null(self::$wechatMdl)){
      self::$wechatMdl = new Weichat();
    }

    return self::$wechatMdl;
  }

  /**
   * 获取短信实例
   * @methods
   * @desc
   * @author slide
   * @return null
   *
   */
  private static function _getSmsMdl(){
    if(is_null(self::$smsMdl)){
      self::$smsMdl = new Sms();
    }

    return self::$msgMdl;
  }

  /**
   * 获取消息发送实例
   * @methods
   * @desc
   * @author slide
   * @return MessageMdl|null
   *
   */
  private static function _getMsgMdl (){
    if(is_null(self::$msgMdl)){
      self::$msgMdl = new MessageMdl();
    }

    return self::$msgMdl;
  }

  /**
   * 任务结果推送
   * @methods
   * @desc
   * @author slide
   *
   */
  public static function taskNotice($uid, $url = '',  $sub_title = "通知", $content="", $title = '任务处理通知', $remake = "谢谢您的支持与厚爱!"){

    $sendRes = self::_getWechatMdl()->sendTemplateMsg( WEB_DOMAIN . $url, 'TASK_PROCESSING', [$title, $sub_title, $content, $remake], $uid);
    if($sendRes) {
      L('任务处理通知模板发送成功');
    }else{
      L('任务处理通知模板发送失败');
    }
  }


}
