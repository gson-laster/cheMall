<?php

namespace app\home\controller;

use think\Cache;
use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use think\Session;
use app\common\model\User as UserModel;
use app\common\model\SmsTemplate;
use \Firebase\JWT\JWT;
class Login extends Controller
{
  protected $userMdl;

  /**
   * @author: slide
   **/
    protected function _initialize(){
    $this->userMdl = new UserModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if(Session::get('qt_userId')){
          $this->redirect('home/user/index');
        }else{
          return  $this->fetch();
        }
    }

    public function SignUp()
    {
        return  $this->fetch();
    }

    /**
     * 会员登录验证
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function login($login_type = '')
    {
      if($this->request->isPost()){
        $data = $this->request->post();
        if(!isset($data['phone']) || $data['phone'] == ''){
          $this->error('电话号码不能为空');
        }
        if($login_type == 'sms'&& cache_data('site_config')['login_sms']){
          if(!isset($data['code']) || $data['code'] == ''){
            $this->error('验证码不能为空');
          }
          if(strtolower($data['code']) !== strtolower(Session::get('sms_code'))){
            $this->error('验证码错误');
          }
        }
        $user = $this->userMdl->field(["id","phone","nickname",'userimg',"password","address_now","user_vb",'parent_agent','agent_type'])->where("phone='".$data['phone']."'")->find();
        if($user){
          if($login_type != 'sms' && md5($data['password'] . Config::get('user_token')) != $user['password']){
            $this->error('用户密码错误');
          }else{
            //登录成功
            Session::set('qt_userId', $user['id']);
            Session::set('qt_userPhone', $user['phone']);
            Session::set('qt_nickname',$user['nickname']);
            Session::set('userInfo', $user->toArray());
            $token = array(
              "iss" => WEB_DOMAIN,
              'userId' => $user['id'],
              'userPhone' => $user['phone'],
              "nbf" => time()
            );
            $jwt = JWT::encode($token, Config::get('user_login_key'));
            $this->userMdl->where("id", $user['id'])->setField('token', $jwt);
            $user->token = $jwt;
            cache_data('user'.$user['id'], $user->toArray());
            userGetNotice($user['id']); // 获取公告信息
            $this->success('登录成功', url('home/index/index'), $user->toArray());
          }
        }else{
          $this->error('没有这样的用户');
        }
      }else{
        $this->error('访问方式错误');
      }
    }

		public function reg(){
      $sms_time = Db::name('config')->find(1)['sms_wait_time'];
			return $this->fetch('reg', ['sms_time'=>$sms_time]);
		}
    /**
     * 短信数据
     * @param    [type]                   $mobile [description]
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-19T17:06:24+080
     */
    public function ajaxSendSms($login_type='', $mobile, $desc = '账号注册的'){
      if($mobile){
        $this->userMdl = new UserModel();
        $user = $this->userMdl->where("phone", $mobile)->find();
        if($login_type!='sms' && $user) return $this->error('电话号码已经存在');
        $sms = new SmsTemplate();
        $res = $sms->where("send_scene='register'")->find();
        $sms_time = Db::name('config')->find(1)['sms_wait_time'];
        if(Cache::get('sms_'.$mobile)) return $this->error('请勿频繁操作');
        $sms_code = generate_code(6);
        Session::set('sms_code', $sms_code);
        Cache::set('sms_'.$mobile,['code' => $sms_code],$sms_time);
        $send_params = ['name'=> Config::get('SMS_SIGN').$desc,'code'=>$sms_code,'sms_sign'=>Config::get('SMS_SIGN')];
        $result = sendSms($mobile,
                          $res['sms_tpl_code'],
                          $res['tpl_content'],
                          json_encode($send_params, true)
                        );
        if(!$result) $this->error('短信发送错误,请联系管理员');
        $result = json_decode($result, true);
        // dump($result);exit;
        $data = [];
        $code = 1000;
        $msg = '发生错误,重新发送';
        if($result['code'] = 25010){
          $data = [
            'mobile' => $mobile,
            'code'  => $sms_code
          ];
          $code = 1002;
          $msg = '发送成功';
        }elseif($result['code'] == 45201){
          $msg = '号码不合法';
          $code = 1003;
        }elseif($result['code'] == 45103){
          $msg = '号码不能为空';
          $code = 1003;
        }
        return json(['msg'=>$msg, 'code'=>$code, 'data'=>$data]);
      }else{
        $this->error('请填写手机号码');
      }
    }

    /**
     * [register 用户注册]
     * @param    Request                  $request [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-23T16:42:50+080
     */
    public function register(){
      $this->userMdl = new UserModel();
      if($this->request->isPost()){
        $data = $this->request->post();
        $user = $this->userMdl->where("phone", $data['phone'])->find();
        if($user) return $this->error('电话号码已经存在');
        //验证
        $validate_result = $this->validate($data, 'User');
        if(!$validate_result){
          $this->error($validate_result);
        }else{
          $code = input('post.code');
          if(strtolower($code) !== strtolower(Session::get('sms_code'))){
            $this->error('验证码错误');
          }else{
            $data['password'] = md5($data['password'] . Config::get('user_token'));
            //处理上下级
            if(isset($data['agent_phone']) && $data['agent_phone'] != ''){
              //填了推荐人号码
              $agent_user_info = $this->userMdl->where('phone', $data['agent_phone'])->find();
              if($agent_user_info){
                $uid = $agent_user_info['id'];
              }else{
                return $this->error('您填入的推荐人不存在');
              }
            }else{
              
              if(input('uid')&&Db::name('user')->find(input('uid'))){
                //有推荐人
                $uid = input('uid'); //推荐人
              }else{
                //无推荐人,进入公司账户
                $uid = $this->userMdl->where('is_employ_agent', 1)->value('id');
              }
            }

            $parent_user_info = $this->userMdl->getUserInfo($uid);
            $data['pid'] = $uid;
            
            // dump($parent_user_info);
            if($parent_user_info){
              if($parent_user_info->agent_type == 1){
                $data['parent_agent'] = $parent_user_info->id;
              }else{
                $data['parent_agent'] = $parent_user_info->parent_agent;
              }
            }
            ///dump($data);exit;
            $this->userMdl->data($data, true);
            $register_res = $this->userMdl->allowField(true)->save();
             //dump($this->userMdl);exit;
            if ($register_res) {
              $register_id = $this->userMdl->id;
              // 给上一级用户发一条系统信息
              $message = new \app\api\controller\Message();
              $phone = substr($this->userMdl->phone, 0, 3) . '****'.substr($this->userMdl->phone, -1, 4);
              $message->sendMsg('您推荐（'.$phone.'）成为商城会员，感谢您的支持！',date('Y-m-d H:i').'有一名会员成功注册商城,请尽快联系会员 ', -1, $uid, 3, 1,true,false);
  
              // 上级用户直接给当前用户发消息
              $message->sendMsg('','欢迎注册【'.Config::get('sms_sign')."】,我将是您的服务商，有任何需要可以联系我!", $uid, $register_id, 1, 1,true,false);
              $user_res = $this->userMdl->find($register_id);
              Session::set('qt_userId',$user_res['id']);
              Session::set('qt_userPhone', $user_res['phone']);
              Session::set('qt_nickname','');
              Session::set('userInfo', $user_res->toArray());
              $token = array(
                "iss" => WEB_DOMAIN,
                'userId' => $user_res['id'],
                'userPhone' => $user_res['phone'],
                "nbf" => time()
              );
              $jwt = JWT::encode($token, Config::get('user_login_key'));
              $this->userMdl->where("id", $user_res['id'])->setField('token', $jwt);
              $user_res->token = $jwt;
              cache_data('user'.$user['id'], $user_res->toArray());
              userGetNotice($user_res['id']); // 获取公告信息
              $parent_user_info->save();
              $this->success('注册成功', url('home/user/index'), $user_res);
            } else {
                $this->error('注册失败');
            }
          }
        }
      }
    }
	//找回密码
		public function findpassword(){
      if($this->request->isPost()){
        $data = $this->request->post();
        $rule = [
          ['phone', 'require', '电话不能为空'],
          ['code', 'require', '验证码不能为空'],
          ['password', 'require|confirm', '密码不能为空|两次密码不一样']  // password_confirm
        ];
        $result = $this->validate($data, $rule);
        if(true !== $result) $this->error($result);
        $code = input('post.code');
        if(strtolower($code) !== strtolower(Session::get('sms_code'))){
          $this->error('验证码错误');
        }
        $user = $this->userMdl->where('phone', $data['phone'])->find();
        if(!$user) return $this->error('没有这样的用户');
        // 重置密码
        $user_data['password'] = md5($data['password'] . Config::get('user_token'));

        $res = $this->userMdl->where("phone", $data['phone'])->update($user_data);

        if($res){
          $this->success('重置成功，现在就去登录吧', url('home/login/index'));
        }else{
          $this->error('重置失败，请重试', url('home/login/FindPassword'));
        }
      }else {
        return $this->fetch();
      }
		}
}
