<?php

namespace app\agentadmin\controller;

use app\common\Hook;
use think\Config;
use think\Session;
use think\Db;
use app\common\model\User;

/**
 * 代理后台登录
 */
class Login extends AgentBase
{
  protected $userMdl;
  protected function _initialize(){
    parent::_initialize();
    $this->userMdl = new User();
  }

  public function index(){
    return $this->fetch();
  }

  /**
   * 代理后台
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T17:12:43+080
   */
  public function login(){
    if($this->request->isPost()){
      $data = $this->request->post();
      $rule = [
        ['username', 'require', '用户名不能为空'],
        ['password', 'require', '密码不能为空'],
        ['captcha', 'require|captcha']
      ];
      $result = $this->validate($data, $rule);
      // dump($result);
      if( true!== $result) return $this->ajax(2003, $result);

      //代理本人
      $user = $this->userMdl->field(["id","phone","nickname","password","agent_type","is_employ_agent"])->where("phone='".$data['username']."'")->find();
      if($user){
        if($user->agent_type !== 1 &&$user->is_employ_agent!=1 ){
          return $this->ajax(2001, '该会员不是代理,无权限登录');
        }
        //判断用户
        if(md5($data['password'] . Config::get('user_token')) != $user['password']){
          return $this->ajax(2001, '用户密码错误');
        }
        //登录逻辑
        session('agent_admin', $user->toArray());
  			setcookie('agent_user_id', $user['id'], null, '/');
        setcookie('agent_user_phone', $user['phone'], null, '/');
        // dump(1);
      } else {
        // 员工
        $employee_res = Db::name('agent_employee')->where('username', $data['username'])->find();
        if($employee_res){
          //判断用户
          if(md5($data['password'] . Config::get('user_token')) != $employee_res['password']){
            return $this->ajax(2001, '用户密码错误');
          }
          session('agent_employee', $employee_res);
          Session::delete('agent_admin');
    			setcookie('agent_user_employee_id', $employee_res['id'], null, '/');
          setcookie('agent_user_employee_username', $employee_res['username'], null, '/');
        }else{
          return $this->ajax(2001, '该会员不存在');
        }
        // dump(2);
      }
      return $this->ajax(2000, '登录成功');
    }else{
      return $this->ajax(4000, '访问方式错误，请联系管理员');
    }
  }
}
