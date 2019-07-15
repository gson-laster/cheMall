<?php
namespace app\admin\controller;
use think\Config;
use think\Db;
use app\common\model\Admin as adminModel;

class Admin extends AdminBase
{
    protected $adminMdl;
    protected function _initialize(){
      parent::_initialize();
      $this->adminMdl = new adminModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
      $list = $this->adminMdl->select();
      $role = Db::name('admin_role')->select();
      return $this->fetch('index', ['list'=>$list,'role'=>$role]);
    }
    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update()
    {
        adminLog('更新管理员信息');
      if($this->request->isPost()){
        $type = input('post.id') > 0 ? 2 : 1;
        $data = $this->request->post();
        $check_data = $data;
  
        if($type == 1){

          $valide_result = $this->validate($check_data, 'Admin');
          if($data['password'] != ''){
            $data['password'] = md5(Config::get('ADMIN_TOKEN').md5($data['password']));
          }else{
            unset($data['password']);
          }
          if($valide_result !== true){
            $this->error($valide_result);
          }

          $this->adminMdl->data($data, true);
          $result = $this->adminMdl->allowField(true)->save();

      	//dump($type);exit;
        }else{
          if(isset($data['password']) && $data['password'] != ''){
            if(!isset($data['password_confirm']) || $data['password'] != $data['password_confirm']){
              $this->error('两次密码不一样');
            }
            //dump($data);
            $data['password'] = md5(Config::get('ADMIN_TOKEN').md5($data['password']));
          }else{
            unset($data['password']);
          }

          $this->adminMdl->data($data, true);
          $result = $this->adminMdl->allowField(true)->isUpdate(true)->save();
          // dump($data);
          // dump($this->adminMdl->getLastSql());exit;
        }
        if($result){
          $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }

      }else{
        $this->error('访问方式错误');
      }
    }

    /**
     * 获取单个管理员信息
     * @param    [type]                   $id [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-04-10T10:28:18+080
     */
    public function getAdminById($id){
      if($id){
        $res = $this->adminMdl->find($id);
        if($res){
          $this->success('获取管理员信息成功', url('index'), $res);
        }else{
          $this->error('获取管理员信息失败');
        }
      }else{
        $this->error("没有这样的管理员");
      }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id = 0, $ids = [])
    {
        adminLog('删除管理员');
      $id = $ids ? $ids : $id;
      if($id){
        $this->request->get();
        if($this->adminMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的管理员');
      }
    }

    /**
     * 修改密码
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-26T16:24:47+080
     */
    public function changePwd($admin_id =0){
        adminLog('修改管理员密码');
      if($this->request->isPost()){
        if(!$admin_id) $this->error('没有这样的管理员账户');
        $data = $this->request->post();
        $rule = [
          ['oldpassword', 'require', '原密码不能为空'],
          ['password', "require",'密码不能为空'],
          ['password', "length:6,32", '密码长度不正确'],
          ['password_confirm', 'confirm:password','两次密码不一样']
        ];
        $validate_result = $this->validate($data, $rule);
        if($validate_result !== true){
          $this->error($validate_result);
        }

        $old = md5(Config::get('ADMIN_TOKEN').md5($data['oldpassword']));
        $admin_info = $this->adminMdl->find($admin_id);

        if($admin_info['password'] !== $old){
          $this->error('原密码不正确');
        }
        $db_datap['id'] = $admin_id;
        $db_datap['password'] = md5(Config::get('ADMIN_TOKEN').md5($data['password']));
        $this->adminMdl->data($db_datap);
        if($this->adminMdl->allowField(true)->isUpdate(true)->save()){
          $this->success('修改密码成功');
        }else{
          $this->error('修改密码错误');
        }
      }else{
        $this->error('访问方式错误');
      }
    }
}
