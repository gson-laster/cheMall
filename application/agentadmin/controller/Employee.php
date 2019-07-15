<?php

namespace app\agentadmin\controller;

use app\agentadmin\model\AgentEmployee as AgentEmployee;
use think\Config;
use think\Session;
use think\Db;
/**
 * 代理员工添加
 */
class Employee extends Common
{

  protected $employeeMdl;
  protected function _initialize(){
    parent::_initialize();
    $this->employeeMdl = new AgentEmployee();
  }

  /**
   * 员工列表
   * @param    string                   $keyword [description]
   * @param    integer                  $page    [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T18:49:39+080
   */
  public function index ($keyword = '', $page = 1) {
    $map = [];
    if($keyword){
      $map['id|username|nickname'] = ['likE', "%'{$keyword}'%"];
    }
    //dump(Session::get('agent_admin'));
    $map['agent_id'] = Session::get('agent_admin') ? Session::get('agent_admin')['id'] : Session::get('agent_employee')['agent_id'];  //代理本人
    $list = $this->employeeMdl->where($keyword)->where($map)->order('createtime DESC')->select();
    
    return $this->fetch('index', ['list' => $list] );
  }

  /**
   * 获取单个员工
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T18:59:20+080
   */
  public function getEmployeeById($id = 0){
    if($id){
      if($res = $this->employeeMdl->find($id)){
        $this->success('请求数据成功', '', $res);
      }else{
        $this->error('请求数据失败,稍后再试');
      }
    }else{
      $this->error('没有这样的员工,请核对后再试');
    }
  }

  /**
   * 保存员工信息
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T19:01:53+080
   */
  public function saveEmployee(){
    if($this->request->isPost()){
      if(!$this->isAgentAdmin()) {
        $this->error('没有操作权限');
      }
      $type = input('post.id') > 0 ? 2 : 1;
      $data = $this->request->post();
      $rule = [
        ['username', 'require|max:32', '用户名格式错误']
      ];
      $result = $this->validate($data, $rule);
      if(true !== $result){
        $this->error($result);
      }
      $data['agent_id'] = session('agent_admin')['id'];
      $this->employeeMdl->data($data, true);
      if($type == 1){
        if(!isset($data['password']) || $data['password'] == ''){
          return $this->error('密码不能为空');
        }
        if($data['password'] !== $data['password_confirm']){
          return $this->error('两次密码不一致');
        }
        $this->employeeMdl->password = md5($data['password'] . Config::get('user_token'));
        $res = $this->employeeMdl->allowField(true)->save();
      }else{
        if(isset($data['password'])){
          $this->employeeMdl->password = md5($data['password'] . Config::get('user_token'));
        }
        $res = $this->employeeMdl->allowField(true)->isUpdate(true)->save();
      }
      if($res){
        $this->success('操作员工信息成功');
      }else{
        $this->error('操作员工信息失败');
      }
    }else{
      $this->error('访问方式错误');
    }
  }

  /**
   * 删除员工信息
   * @param    string                   $id  [员工id]
   * @param    [type]                   $ids [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T19:14:46+080
   */
  public function delEmployee($id = ''){
    if($this->isAgentAdmin()){
      $employee_res = $this->employeeMdl->find($id);
      if($employee_res['agent_id'] !== session('agent_admin')['id']) $this->error('您公司不存在这样的员工');
      if($id){
        $this->request->get();
        if($this->employeeMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的员工');
      }
    }else{
      $this->error('没有操作权限');
    }
  }
  
  /**
   * 保存员工客服并生成消息系统uuid
   * @author: slide
   *
   * @param $id
   *
   */
  public function createService($id = 0) {
    if($id){
      $uuid = uuid('S');
      $data = ['msg_uid' => $uuid, 'is_service'=>1];
      $res = Db::name('agent_employee')->where('id', $id)->update($data);
      if($res){
        $this->success('客服设置成功');
      }else{
        $this->error('客服设置失败');
      }
    }else{
      $this->error('没有这样的员工');
    }
  }
}
