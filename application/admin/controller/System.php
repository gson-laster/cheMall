<?php

namespace app\admin\controller;

use think\Db;

use app\admin\model\SystemMenu;
/**
 * 系统相关管理
 */
class System extends AdminBase
{
  protected $menuMdl;
  public $menu_group = [];
  protected function _initialize(){
    parent::_initialize();
    $this->menuMdl = new SystemMenu();
    $this->menu_group = [
      'system'    => ['icon-cogs', '系统设置'],
      'goods'     => ['icon-desktop', '商品管理'],
      'service'     => ['icon-desktop', '服务中心'],
      'weichat'   => ['icon-credit-card', '微信管理'],
      'transport' => ['icon-laptop', '运费管理'],
      'admin'     => ['icon-group', '管理员管理'],
      'order'     => ['icon-list', '交易管理'],
      'member'    => ['icon-user', '会员管理'],
      'article'   => ['icon-bookmark', '文章管理'],
      'count'     => ['icon-table','统计报表'],
      'message'   => ['icon-table','消息管理']
    ];
  }

  /**
   * 菜单列表
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T14:43:08+080
   */
  public function menuList(){
    $list = $this->menuMdl->select();
    $list_res = array2level($list);
    // dump($list_res);exit;
    $this->assign('group', $this->menu_group);
    return $this->fetch('menu-list',['list'=>$list_res]);
  }

  /**
   * 添加或者更新菜单
   * @Author:  slade
   * @DateTime :2017-04-20T14:43:47+080
   */
  public function addEditMenu(){

      adminLog('添加或者更新菜单');
    if($this->request->isPost()){
      $data = $this->request->post();
      // dump($data);exit;
      $type = input('post.id') > 0 ? 2 : 1;
      $this->menuMdl->data($data, true);
      if($type == 1){
        $result = $this->menuMdl->save();
      }else{
        $result = $this->menuMdl->isUpdate(true)->save();
      }
      if($result){
        $this->success('操作成功', url('menuList'));
      }else{
        $this->error('操作失败');
      }
    }else{
      $this->error('访问方式错误');
    }
  }

  /**
   * 获取单个菜单
   * @param    [type]                   $id [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T14:56:45+080
   */
  public function getMenuById($id){
    if($id){
      $res = $this->menuMdl->find($id);
      if($res){
        $this->success('获取成功', url('menuList'), $res);
      }else{
        $this->error('获取失败');
      }
    }else{
      $this->error('没有这样的系统菜单');
    }
  }

  /**
   * 删除菜单
   * @param    string                   $id  [description]
   * @param    [type]                   $ids [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T18:22:49+080
   */
  public function deleteMenu($id = '', $ids = []){

      adminLog('删除菜单');
    $id = $ids ? $ids : $id;
    if($id){
      $this->request->get();
      if($this->menuMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的菜单');
    }
  }

  /**
   * 管理员角色列表
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T15:13:25+080
   */
  public function roleList(){
    $list = Db::name('admin_role')->order('role_id desc')->select();
    $this->assign('list',$list);
    return $this->fetch();
  }

  /**
   * 添加修改橘角色
   * @Author:  slade
   * @DateTime :2017-04-24T09:17:05+080
   */
  public function addEditRole(){

      adminLog('添加修改橘角色');
  	if(input('role_id')){
      $role_id = input('role_id');
  		$detail = Db::name('admin_role')->where("role_id",$role_id)->find();
      	$detail['act_list'] = explode(',', $detail['act_list']);
  		$this->assign('role_info', $detail);
  	}
    // dump($detail);
  	$right = Db::name('system_menu')->order('id DESC')->select();
    $menu = setMenu($right, 0);
    // dump($menu);
  	$this->assign('menu', $menu);
    $this->assign('group', $this->menu_group);
  	return $this->fetch();
  }

  /**
   * 保存或者添加角色
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T15:18:50+080
   */
  public function roleSave(){
      adminLog('添加角色');
    if($this->request->isPost()){
      $data = input('post.');
      $res = $data;
      $res['act_list'] = is_array($data['act_list']) ? implode(',', $data['act_list']) : '';
      $admin_role = Db::name('admin_role');
      if(!isset($data['role_id']) || empty($data['role_id'])){
        $r = $admin_role->insert($res);
      }else{
        $r = $admin_role->where('role_id', $data['role_id'])->update($res);
      }
      if($r){
        // adminLog('管理角色');
        $this->success("操作成功!",url('Admin/system/roleList',array('role_id'=>$admin_role->getLastInsID())));
      }else{
        $this->error("操作失败!",url('Admin/Admin/role'));
      }
    }else{
      $this->error('访问方式错误');
    }
  }

  /**
   * 角色详情
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T15:29:23+080
   */
  public function role_info(){
    $role_id = input('get.role_id/d');
    $detail = array();
    if($role_id){
      $detail = Db::name('admin_role')->where("role_id",$role_id)->find();
      $detail['act_list'] = explode(',', $detail['act_list']);
      $this->assign('detail',$detail);
    }
    $right = Db::name('system_menu')->order('id')->select();
    foreach ($right as $val){
      if(!empty($detail)){
        $val['enable'] = in_array($val['id'], $detail['act_list']);
      }
      $modules[$val['group']][] = $val;
    }
    //权限组
    $this->assign('group',$this->menu_group );
    $this->assign('modules',$modules);
    return $this->fetch();
  }
  /**
   * 角色删除
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-20T15:26:14+080
   */
  public function roleDel(){

      adminLog('删除角色');
    $role_id = input('post.role_id/d');
    if(!$role_id) return $this->error('请选择要删除的角色');
    $admin = Db::name('admin')->where('admin_role='.$role_id)->find();
    if($admin){
      $this->error("请先清空所属该角色的管理员");
    }else{
      $d = Db::name('admin_role')->where("role_id", $role_id)->delete();
      if($d){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }
  }
}
