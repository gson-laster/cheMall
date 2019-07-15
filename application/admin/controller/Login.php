<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Config;
use think\Session;
/**
 * 后台登录
 */
class Login extends Controller
{

  protected function _initialize(){
    parent::_initialize();
  }

  /**
   * 登录动作
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-22T11:00:13+080
   */
  public function index(){

    return $this->fetch();
  }

  /**
   * 管理员登录
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-22T14:43:12+080
   */
  public function login(){
    if($this->request->isPost()){
      $data = $this->request->post();
      if(!isset($data['username']) || $data['username'] == ''){
        $this->error('没有用户名');
      }
      if(!isset($data['password']) || $data['password'] == ''){
        $this->error('没有密码');
      }
      $result = $this->validate($data,[
          'captcha|验证码'=>'require|captcha'
      ]);
      if($result !== true){
        $this->error($result);
      }
      $admin = Db::name('Admin');
      $admin_res = $admin->where("user_name", $data['username'])->find();
      if($admin_res){
        if($admin_res['password'] !== md5(Config::get('ADMIN_TOKEN').md5($data['password']))){
          $this->error('密码错误');
        }
        if($admin_res['status'] !== 1) $this->error('您的管理员账户已经被锁定，请联系系统管理员', '','', 5);
        //登录操作
        $ip = getIp();
        $admin->where("user_name", $data['username'])
              ->inc('login_count',1)
              ->exp('last_login_time',time())
              ->exp('last_login_ip', "'".$ip."'")->update();
        Session::set('admin_id', $admin_res['id']);
        Session::set('admin_info', $admin_res);
        adminLog('管理登录');
        $this->success('登录成功', url('admin/index/index'));
      }else{
        $this->error('没有这样的管理员');
      }
      // md5(Config::get('ADMIN_TOKEN').md5($data['password']));
    }else{
      $this->error('访问方式出错');
    }
  }
}
