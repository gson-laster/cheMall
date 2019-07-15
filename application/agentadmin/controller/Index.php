<?php
/**
 * Created by PhpStorm.
 * User: slade
 * Date: 2017/5/20 0020
 * Time: 下午 3:00
 */

namespace app\agentadmin\controller;


use app\common\model\AgentGetmoneyInfo;
use think\Db;
use think\Session;

class Index extends Common {
  protected $agentGetMoneyMdl;
  protected function _initialize(){
    parent::_initialize();
    if($this->isAgentAdmin()){
      $this->agent_id = Session::get('agent_admin')['id'];
    }else{
      $this->agent_id = Session::get('agent_employee')['agent_id'];
    }
    $this->agentGetMoneyMdl = new AgentGetmoneyInfo();
  
    $this->service = Db::name('agent_employee')->where(['is_service'=>1,'agent_id'=> $this->agent_id])->find();
  }
  /**
   * 首页框架
   * @author: slide
   * @return mixed
   *
   */
  public function index () {
   
    return $this->fetch();
  }
  
  /**
   * 欢迎
   * @author: slide
   * @return mixed
   *
   */
  public function welcome(){
    // 获取代理收款信息
    $res = $this->agentGetMoneyMdl->where('user_id', $this->agent_id)->find();
    
    return $this->fetch('welcome', ['result' => $res]);
  }
  
  /**
   * 保存收款信息
   * @author: slide
   *
   * @param $data
   *
   */
  public function saveGetMoneyInfo(){
    if($this->request->isPost()){
      $type = input('id') ? 2 : 1;
      $data = $this->request->post();
      $data['user_id'] = $this->agent_id;
      $this->agentGetMoneyMdl->data($data, true);
      if($type == 1){
        $res = $this->agentGetMoneyMdl->allowField(true)->save();
      }else{
        $res = $this->agentGetMoneyMdl->allowField(true)->isUpdate(true)->save();
      }
      if($res){
        $this->success('保存成功');
      }else{
        $this->error('保存失败');
      }
    }else{
      $this->error('访问方式错误');
    }
  }
  /**
   * 退出
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-26T14:33:17+080
   */
  public function login_out(){
    session('agent_admin', null);
    cookie('agent_user_id', null);
    cookie('agent_user_phone', null);
    $this->success('退出成功', url('agentadmin/login/index'));
  }
  
  public function messaage_list () {
    $this->assign('service', $this->service);
    return $this->fetch();
  }
  
}
