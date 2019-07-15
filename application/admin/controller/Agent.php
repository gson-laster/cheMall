<?php
namespace app\admin\controller;

use app\api\controller\Message;
use think\Db;
use think\Config;
use app\common\model\User;
use app\home\controller\Weichat;
use think\Log;
/**
 * 代理操作
 */
class Agent extends AdminBase
{

  /**
   * 列表
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-14T10:09:35+080
   */
  public function index(){
    $res = Db::name('agent')->select();
    $agent = $res;
    $agent[] = $res[0];
    array_shift($agent);
    cache_data('agent', $agent);
    return $this->fetch('index', ['list' => $res]);
  }

  /**
   * 保存   只更新 【name/tc_proportion/free】
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-14T10:09:55+080
   */
  public function save(){
    if($this->request->isPost()){
      if(!input('id')) $this->error('缺少更新条件');
      $data = $this->request->post();
      $data['updatetime'] = time();
      $res = Db::name('agent')->where('id', input('post.id'))->update($data);
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
   * 代理申请列表
   * @Author:  slade
   * @DateTime :2017-05-15T09:58:21+080
   */
  public function AgentApply($page = 1){
    $apply_list = Db::name('agentApply')->alias('ag')->field(['ag.*','u.phone','u.nickname','wx.nickname as wx_nickname','wx.headimgurl','pu.phone as parent_phone', 'pu.nickname as parent_nickname', 'pwx.nickname as parent_wx_nickname', 'pwx.headimgurl as parent_wx_headimgurl'])->join('__USER__ u', 'u.id=ag.user_id', 'left')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','LEFT')->join('__USER__ pu', 'pu.id=u.pid', 'LEFT')->join('__USER_WEICHAT_INFO__ pwx','pwx.id=pu.bindwx','LEFT')->order('createtime DESC')->paginate(Config::get('pageSize', false, ['page'=>$page]));
    $status = [0 => '待审核', 1 => '通过', 2 => '不通过'];
//    dump($apply_list);
    return $this->fetch('agent_apply_list', ['list' => $apply_list, 'status' => $status]);
  }

  /**
   * 获取申请记录
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-15T10:04:30+080
   */
  public function getAgentApplyById($id = ''){
    if($id){
      $info = Db::name('agentApply')->find($id);
      if($info){
        $this->success('查询成功', '', $info, 0);
      }else{
        $this->error('查询失败，请重试');
      }
    }else{
      $this->error('缺少必要条件');
    }
  }

  /**
   * 保存信息
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-15T10:07:00+080
   */
  public function saveAgentApply(){
    if($this->request->isPost()){
      $type = input('post.id') ? 2 : 1;
      $data = $this->request->post();
      $rule = [
        ['user_id', 'require', '请选择用户'],
        ['agent_type', 'require', '请选择代理等级']
      ];
      $result = $this->validate($data, $rule);
      if(true !== $result) $this->error($result);
      if($type === 1) {
        $res = Db::name('agent_apply')->insert($data);
      }else{
        $res = Db::name('agent_apply')->where('id', input('post.id'))->update($data);
      }

      if($res){
        $this->success('操作成功');
      }else{
        $this->error('操作失败');
      }
    }else{
      $this->error('访问方式错误');
    }
  }

  /**
   * 同意代理申请
   * @author: slide
   * @param $apply_id 申请记录的id
   * @return mixed
   */
  public function agreeAgentApply($apply_id, $status){

    $result = (new User())->agentApplyHandler($apply_id, $status, function ($result) {
      L('result'.$result);
      if($result) {
        return json( ['code'=>2000, 'msg'=>'更新成功'] );
      }else{
        return json(['code'=>4000, 'msg'=>'更新失败']);
      }
    });
  }

  /**
   * 代理树
   * @author: slide
   */
  public function agentTree($pid){
    if($pid){
      $user_list = Db::name('user')->where('pid', $pid)->select();
    }else{
      $user_list = Db::name('user')->where('agent_type', 0)->select();
    }
    if($this->request->isAjax()){
      return $this->ajax(2000, '查询成功', '', $user_list);
    }else{
      $this->assign('list', $user_list);
      return $this->fetch();
    }
  }

}
