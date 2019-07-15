<?php
namespace app\api\controller;
use think\Controller;
use think\Config;
use think\Log;
use app\common\model\SmsTemplate;
/**
 *
 */
class Sms extends Controller
{
  protected $smsMdl;
  protected function _initialize(){
    parent::_initialize();
    $this->smsMdl = new SmsTemplate();
  }

  /**
   * 发送短信接口
   * @param    string                   $mobile [电话]
   * @param    string                   $scren  [场景]
   * @param    [type]                   $data   [数据]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-19T17:26:31+080
   */
  public function sendSMS($mobile='', $scren='', $data=[])
  {
      Log::write('手机号'.$mobile,'debug','notice');
      Log::write('场景'.$scren,'debug','notice');
      Log::write('数据'.json_encode($data, true),'debug','notice');
      if(!$mobile || $mobile == '' || !$scren || $scren == '' || !$data||empty($data)){
  			return false;
  		}
      $sms_data = $this->setData($scren, $data);
      Log::write('数据评完'.json_encode($sms_data, true), 'info');
      // dump($sms_data);
      $datas = array
      (
        'dev_id'            => Config::get('SMS_DEV_ID'),
        'sign'              => md5(Config::get('SMS_DEV_ID').Config::get('SMS_DEV_KEY').$mobile),
        'rec_num'           => $mobile,
        'sms_param'         => json_encode($sms_data['sms_param'], true),
        'sms_template_code' => $sms_data['smsId'],
        'sms_sign'          => Config::get('SMS_SIGN'),
      );
      //即时发送
      $res = httpRequest(Config::get('SMS_SEND_URL'),'POST', $datas);//POST方式提交
      Log::write('短信发送结果'.$res,'debug','notice');
      if($res){
          return $res;
      }else{
          return false;
      }
  }
  /**
   * 组装数据
   * @param    [type]                   $scren [description]
   * @param    [type]                   $data  [description]
   * @Author:  slade
   * @DateTime :2017-04-19T17:39:23+080
   */
  public function setData($scren, $data){
    // dump('scren'.$scren);
    // dump($data);

    if(!$scren || !$data || $scren==''||empty($data)) return [];
    // dump($scren);
    $result = [];
    $sms_param_data = [];
    switch (strtolower($scren)) {
      case 'register':
        $sms_param_data = ['site_name', 'code', 'minute'];
        break;
      case 'login':
        $sms_param_data = ['site_name', 'code', 'minute'];
        break;
      case 'forget_password':
        $sms_param_data = ['site_name', 'code', 'minute'];
        break;
      case 'do_order_success':
        $sms_param_data = ['site_name', 'order_code'];
        break;
      case 'do_pay_success':
        $sms_param_data = ['site_name', 'order_sn', 'payment'];
        break;
      case 'do_send_goods_success':
        $sms_param_data = ['site_name', 'order_sn', 'consignee', 'shipping_code'];
        break;
      case 'do_get_money_form_other':
        $sms_param_data = ['site_name', 'price'];
        break;
      case 'change_phone':
        $sms_param_data = ['user_name', 'code'];
        break;

      default:
        # code...
        break;
    }
    //组装数据
    foreach ($sms_param_data as $k => $v) {
      $result['sms_param'][$v] = $data[$k];
    }

    $result['smsId'] = $this->smsMdl->where("send_scene='".strtolower($scren)."'")->value('sms_tpl_code');

    // dump($result);exit;
    return $result;
  }
}
