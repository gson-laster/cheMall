<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 下午 4:09
 */

namespace app\home\controller;

/**
 * 用余额管理
 * Class UserRemainder
 *
 * @package app\home\controller
 */
class UserRemainder extends HomeBase {

  /**
   * 用户余额首页
   * @methods
   * @desc
   * @author slide
   */
  public function index () {

    return $this->fetch();
  }

  /**
   * 用户充值（调用附近的服务中心）
   * @methods
   * @desc
   * @author slide
   *
   */
  public function selfRechage(){

    return $this->fetch();
  }
}
