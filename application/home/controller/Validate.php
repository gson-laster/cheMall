<?php
namespace app\home\controller;

use think\Session;
use think\Db;
use \Firebase\JWT\JWT;
use think\Config;

/**
 * 前台校验类
 */
class Validate extends HomeBase {
  
  protected function _initialize () {
    parent::_initialize();
    $shop_car_num = 0;
    $has_no_read_message = false;
    if(Session::get( 'qt_userId' ) ){
      $shop_car_num = Db::name( 'shopcar' )->where( 'user_id=' . Session::get( 'qt_userId' ) )->count();
      $has_no_read_message = Db::name('message_consignee')->where('consignee_id', Session::get('qt_userId'))->where('message_status', 0)->find() ? true : false;
    }
  
    $this->assign('shop_car_num', $shop_car_num);
    $this->assign('has_no_read_message', $has_no_read_message);
    $this->assign( 'wxShare', false );
    $routes = [ 'home/User/index', 'home/User/collect', 'home/User/edit', 'home/User/save', 'home/Myorders/getorder', 'home/Goods/ajaxaddcollect', 'home/User/login_out', 'home/Address/index', 'home/Address/save', 'home/Order/index', 'home/Order/add', 'home/Order/getshippingarea', 'home/Order/countprice', 'home/Shopcar/delete', 'home/Shopcar/add', 'home/Shopcar/index', 'home/User/mywallet', 'home/Order/cancel', 'home/Order/getshipping', 'home/Comment/editorcomment', 'home/Comment/addcomment', 'home/Order/topay', 'home/User/realname', 'home/Order/detail','home/Message/getusermsglist','home/Message/specialmsg','home/Message/dialoglist'];
    //当前访问的地址
    $module     = request()->module();
    $controller = request()->controller();
    $action     = request()->action();
    $path       = $module . '/' . $controller . '/' . $action;
    $this->checkLogin($path, $routes);
  }
  
  private function checkLogin($path, $routes){
    // dump($path);exit;
    $user_res = Db::name('user')->find(Session::get('qt_userId'));
    if ( ( !Session::get( 'qt_userId' ) || is_null( $user_res || is_null(Session::get( 'qt_userId')) ) ) ) {
      //校验登录
      //dump(Session::get( 'qt_userId' ));exit;
      if ( in_array( $path, $routes ) ) {
        /*$decoded     = JWT::decode( $user_res[ 'token' ], Config::get( 'user_login_key' ), [ 'HS256' ] );
        $decoded_arr = (array) $decoded;
        if ( $decoded_arr[ 'nbf' ] + 1800000 < time() ) {
          Session::delete( 'qt_userId' );
          Session::delete( 'qt_userPhone' );
          Session::delete( 'userInfo' );
          cache_data( 'user' . Session::get( 'qt_userId' ), null );
          Db::name( 'user' )->where( 'id', Session::get( 'qt_userId' ) )->setField( 'token', '' );
          $this->error( '登录超时，请重新登录', url( 'home/login/index' ) );
        }*/
        if ( $this->request->isAjax() ) {
          return $this->error( '没有登录', url( 'home/login/index' ), [ 'data' => 'error' ], 3 );
        } else {
          $this->redirect( url( 'home/login/index' ) );
        }
      }
    }
  }
}
