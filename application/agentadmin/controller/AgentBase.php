<?php


namespace app\agentadmin\controller;

use think\Controller;

/**
 * 代理后台基本类
 */
class AgentBase extends Controller
{
  protected function _initialize(){
    parent::_initialize();
    //是否是代理本人
    $this->assign('is_agent_admin', $this->isAgentAdmin());
  }

  /**
   * ajax 返回数据
   * @param    string                   $code [description]
   * @param    string                   $msg  [description]
   * @param    string                   $url  [description]
   * @param    [type]                   $data [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T17:03:00+080
   */
  public function ajax($code = '', $msg = '', $url = '', $data = []){
    return json($this->ajaxArr ($code, $msg, $url, $data));
  }

  /**
   * 返回ajax数组
   * @param    string                   $code [description]
   * @param    string                   $msg  [description]
   * @param    string                   $url  [description]
   * @param    [type]                   $data [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-13T17:03:41+080
   */
  public function ajaxArr ($code = '', $msg = '', $url = '', $data = []) {
    return [
      'code' => $code,
      'msg'  => $msg,
      'url'  => $url,
      'data' => $data
    ];
  }

  /**
   * 是否是代理本人
   * @return   boolean                  [description]
   * @Author:  slade
   * @DateTime :2017-05-13T18:57:10+080
   */
  public function isAgentAdmin(){
    return session('agent_admin') ? true : false;
  }
}

