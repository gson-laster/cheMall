<?php
namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Db;
/**
* 后台模块基本类
*/
class AdminBase extends Controller {

	protected function _initialize(){
		parent::_initialize();
		// dump(Session::get('admin_id'));
//		 dump($this->check_login());exit;
		if(!$this->check_login()){
			$this->error('还没有登录呢', url('admin/login/index'));
		}
	}

	/**
	 * 校验管理员登录
	 * @return   [type]                   [description]
	 * @Author:  slade
	 * @DateTime :2017-04-22T15:19:45+080
	 */
	public function check_login(){
		$res = Db::name('admin')->find(Session::get('admin_id'));
		$this->assign('admin_info', $res);
    //当前访问的地址
    $module = request()->module();
    $controller = request()->controller();
    $action = request()->action();
    $path = $module .'/'. $controller .'/'. $action;
    //无需校验
    $auth_access = [
      'admin/Index/index',
      'admin/Index/home',
      'admin/Goods/ajaxgetspecselect',
      'admin/Goods/ajaxgetattrinput',
      'admin/Goods/ajaxgetspecinput',
      'admin/Order/order_action',
      'admin/Index/login_out',
      'home/Weichat/createwechatmenu',
      'admin/Message/binduid',
      'admin/Message/get_not_read_message',
      'admin/Message/dialoglist'
    ];

		if($res && (Session::get('admin_id'))){
      $this->checkAuth($res, $path, $auth_access);
			return true;
		}else{
      return false;
		}
	}

	/**
	 * 检查权限
	 * @return   [type]                   [description]
	 * @Author:  slade
	 * @DateTime :2017-04-25T11:49:24+080
	 */
	public function checkAuth($admin_info, $path, $auth_access){

		//管理员角色
		$role_id = $admin_info['admin_role'];

		//角色权限
		$role_act_list = Db::name('admin_role')->find($role_id);
		//是否超级管理员||是否包含
		if(!$admin_info['is_super']){
			//全部菜单
			$where['id'] = ['exp', ' IN ('.$role_act_list['act_list'].')'];
			$menu_list = Db::name("system_menu")->where($where)->select();
			$menu_arr = $this->createLeftMenu($menu_list);
		}else{
			$menu_list = Db::name("system_menu")->select();
			$menu_arr = $this->createLeftMenu($menu_list);
		}

		if(!in_array($path, $menu_arr['auth_action']) && !in_array($path, $menu_arr['menu_url']) && !in_array($path, $auth_access)){
				$this->error('没有权限,请联系管理员', url("admin/index/home"));
		}
		$this->assign('left_menu', ['second_html'=>$menu_arr['second_html'],'group'=>$menu_arr['group']]);
	}

	/**
	 * 创建左侧菜单
	 * @param    [type]                   $menu_arr [description]
	 * @return   [type]                   [description]
	 * @Author:  slade
	 * @DateTime :2017-04-25T14:05:04+080
	 */
	public function createLeftMenu($menu_arr){
		$menu_group = [
      'system'    => ['icon-cogs', '系统设置'],
      'service'     => ['icon-desktop', '服务中心'],
      'weichat'   => ['icon-credit-card', '微信管理'],
      'goods'     => ['icon-desktop', '商品管理'],
//    'transport' => ['icon-laptop', '运费管理'],
      'article'   => ['icon-bookmark', '文章管理'],
      'order'     => ['icon-list', '交易管理'],
      'member'    => ['icon-user', '会员管理'],
      'count'     => ['icon-table','统计报表'],
      'message'   => ['icon-table','消息管理'],
      'admin'     => ['icon-group', '管理员管理'],
    ];

		$html_arr = [];
		$html_arr_first = [];
		$auth_action = [];
		$menu_url = [];
		foreach ($menu_arr as $k => $v) {
			//dump($v);
			if($v['pid'] == 0){
				$html_arr[] = $v;
				$menu_url[] = $v['url'];
			}else{
				$auth_action[] = $v['url'];
			}
		}
		return [
			'second_html'=> $html_arr,
			'group' => $menu_group,
			'auth_action' => $auth_action,
			'menu_url'		=> $menu_url
		];
	}

	public function ajax($code = 0, $msg = '', $url = '', $data = []){
			$data = [
				"code" => $code,
				"msg"	 => $msg,
				'url'	 => $url,
				'data' => $data
			];
			return json($data);
	}
}
