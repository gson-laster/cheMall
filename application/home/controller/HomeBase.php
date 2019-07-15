<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
use EasyWeChat\Foundation\Application;
use app\home\controller\Weichat;
use think\Session;

/**
* 后台模块基本类
*/
class HomeBase extends Controller {
	public static $site_config = null;
	public $weichat_app;
	protected function _initialize(){
		self::$site_config = is_null(self::$site_config) ? $this->getSiteConfig() : self::$site_config;
    $shop_car_num = 0;
    $has_no_read_message = false;
    if(Session::get( 'qt_userId' ) ){
      $shop_car_num = Db::name( 'shopcar' )->where( 'user_id=' . Session::get( 'qt_userId' ) )->count();
      $has_no_read_message = Db::name('message_consignee')->where('consignee_id', Session::get('qt_userId'))->where('message_status', 0)->find() ? true : false;
    }
    
    $this->assign('shop_car_num', $shop_car_num);
    $this->assign('has_no_read_message', $has_no_read_message);
		$this->assign('wxShare', true);
		$this->weichat_app = new Weichat();
		$weichat_jsConfig = $this->weichat_app->easy_app->js;
		$this->assign('weichat_config', $weichat_jsConfig);
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

	public function getSiteConfig(){
		return Db::name('config')->find(1);
	}
}
