<?php
namespace app\admin\controller;
use app\common\Hook;
use app\common\model\AgentGetmoneyInfo;
use app\common\model\User as UserModel;
use app\common\model\UserRealname as UserRealname;
use think\Config;
use think\Db;
use think\Session;
use think\Log;
use app\home\controller\Weichat;
use think\Controller;
use app\common\model\AgentNeedGetmoney;
/**
 * 用户管理控制器
 */
class User extends AdminBase{

  protected $userMdl;
  protected function _initialize() {
      parent::_initialize();
      $this->userMdl = new UserModel();
  }

  /**
   * [index 用户列表]
   * @page int|boolean       数字时或者不传时表示有分页 传false时表示没有分页 desc asc
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-22T23:32:12+080
   */
   public function index($agent_type = '', $order='id', $direction='desc', $keywords = '', $start='',$is_privilege='', $page = 1) {
       $map = [];
       $old_agent_type = $agent_type;
       if ($keywords) {
           $map['u.id|u.phone|u.email'] = ['like', "%{$keywords}%"];
       }
       if($start){
         $map['u.createtime'] = [['>', strtotime($start)], ['<', (strtotime($start) + 86400000)]];
       }
       if($is_privilege && $is_privilege != ''){
         $map['u.is_privilege'] = $is_privilege;
       }
       if($agent_type){
         $agent_type = $agent_type == 10 ? 0 : $agent_type;
         $map['u.agent_type'] = $agent_type;
       }
       $order_str = $order .' '.$direction;
       //dump($page);exit;
       if($page == 'false'){
         //dump(1);
         $user_list = $this->userMdl->alias('u')->field(['u.*','uu.nickname as parent_nickname', 'uu.phone as parent_phone','wx.nickname as wx_nickname','wx.headimgurl'])->where($map)->join('__USER__ uu','uu.id=u.pid','LEFT')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','LEFT')->where($map)->order($order_str)->select();
       }else{
         $user_list = $this->userMdl->alias('u')
         ->field(['u.*','uu.nickname as parent_nickname', 'uu.phone as parent_phone','wx.nickname as wx_nickname','wx.headimgurl'])
         ->where($map)->join('__USER__ uu','uu.id=u.pid','LEFT')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','LEFT')
         ->order($order_str)
         ->paginate(Config::get('pageSize'), false, ['page' => $page,"query"=>['agent_type'=>$old_agent_type, 'keywords'=>$keywords,'order'=>$order,'direction'=>$direction,'start'=>$start,'is_privilege'=>$is_privilege]]);
       }
       $agent_info = Db::name('agent')->select();
        //dump( $user_list);exit;

       if($this->request->isAjax()){
          return json($user_list);
       }else{
         $page = $user_list->render();
         return $this->fetch('index', ['agent_info'=>$agent_info,'user_list' => $user_list->toArray(),'page'=>$page, 'keyword' => $keywords]);
       }
   }

  /**
   * [add 添加用户]
   * @param    [type]                   $id [description]
   * @Author:  slade
   * @DateTime :2017-03-22T23:32:03+080
   */
  public function add() {
      adminLog('添加用户');
    if($this->request->isPost()){
      $data = $this->request->post()['user'];
      //验证
      $validate_result = $this->validate($data, 'User');
     // dump($data);exit;
      if ($validate_result !== true) {
          $this->error($validate_result);
      } else {
          $data['password'] = md5($data['password'] . Config::get('user_token'));
          if ($this->userMdl->allowField(true)->save($data)) {
              $this->success('保存成功');
          } else {
              $this->error('保存失败');
          }
      }
    }
  }

  /**
   * [update 更新用户]
   * @param    [array]                   $user [description]
   * @return   {[mixed]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:04:30+080
   */
  public function update($user=[]){
      adminLog('更新用户');
    if($user['id']){
      $data = $this->request->post()['user'];
      $user           = $this->userMdl->find($data['id']);
      $user->id       = $data['id'];
      $user->phone    = isset($data['phone']) ? $data['phone']: $user['phone'];
      $user->email    = isset($data['email']) ? $data['email']: $user['email'];
      $user->sex      = isset($data['sex']) ? $data['sex']: $user['sex'];
      $user->status   = isset($data['status']) ? $data['status'] : $user['status'];
      $user->pid      = isset($data['pid']) ? $data['pid'] : $user['pid'];
      $user->agent_type = isset($data['agent_type']) ? $data['agent_type'] : $user['agent_type'];
      $user->nickname  = isset($data['nickname']) ? $data['nickname'] : $user['nickname'];
      $user->userimg  = isset($data['userimg']) ? $data['userimg'] : $user['userimg'];
      $user->pid  = isset($data['pid']) ? $data['pid'] : $user['pid'];
      $user->parent_agent  = isset($data['parent_agent']) ? $data['parent_agent'] : $user['parent_agent'];
      $user->is_privilege  = isset($data['is_privilege']) ? $data['is_privilege'] : $user['is_privilege'];
      if (!empty($data['password']) && !empty($data['confirm_password'])) {
          $user['password'] = md5($data['password'] . Config::get('user_token'));
      }
      $res = $user->save();
      // dump($user->getLastSql());exit;
      if ($res !== false) {
          $this->success('更新成功');
      } else {
          $this->error('更新失败');
      }
    }else{
      $this->error('缺少必要参数');
    }
  }

  /**
   * [getuserbyid 根据iid返回用户信息]
   * @param    [type]                   $id [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:57:05+080
   */
  public function getuserbyid($id){
    if($id){
      $result = $this->userMdl->find($id);
      // dump($this->userMdl->getLastSql());
      if($result){
        $this->success('查询成功', url('index'), $result);
      }else{
        $this->error('查询失败');
      }
    }else{
      $this->error("缺少必要参数");
    }
  }

  /**
   * [delete 删除会员]
   * @param    [type]                   $id [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:29:07+080
   */
  public function delete($id = 0, $ids = []) {
      adminLog('删除用户');
    $id = $ids ? $ids : $id;
    if($id){
      $this->request->get();
      if($this->userMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的会员');
    }
  }

  /**
   * 用户下级
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-02T10:52:46+080
   */
  public function userSub($userId){
    if($userId){
      $map['p3'] = $userId;
      $res = $this->userMdl->where($map)->select();
      foreach ($res as $key => &$value) {
        if($this->userMdl->field(['id'])->where('p3', $value['id'])->find()){
          $value['hasChild'] = true;
        }else{
          $value['hasChild'] = false;
        }
      }
      // dump($this->userMdl->getLastSql());
      // dump($res);
      if($res){
        $this->success('查询成功', '', $res);
      }else{
        $this->error('查询失败');
      }
    }else{
      $this->error('缺少必要条件');
    }
  }

//================================================================================
  /**
   * 用户实名信息列表
   * @param    string                   $keywords   [description]
   * @param    string                   $start_time [description]
   * @param    string                   $endtime    [description]
   * @param    integer                  $page       [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-04T09:50:31+080
   */
  public function realname_list($keywords='', $start_time='',$end_time='',$page=1){
    $real = new UserRealname();
    $map = [];
    if($keywords){
      $map['real_name|real_phone|bank_card_code|bank_name'] = ['LIKE',"%{$keywords}%"];
    }
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
    }
    $real_list = $real->where($map)->order('createtime DESC')->paginate(Config::get('pageSize'), false, ['page'=>$page]);
    return $this->fetch('real_name_list', ['list'=>$real_list]);
  }
  /**
   * 获取单个实名信息
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-04T09:42:48+080
   */
  public function getRealNameById($id = ''){
    if($id){
      $real = new UserRealname();
      $res = $real->find($id);
      if($res){
        $this->success('获取成功', '', $res);
      }else{
        $this->error('获取失败');
      }
    }else{
      $this->error('没有这样的信息');
    }
  }

  /**
   * 保存实名信息
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-04T09:44:54+080
   */
  public function saveRealName($id=''){
    if($id){
      $realMdl = new UserRealname();
      $data = $this->request->post();
      $rule = [
        ['real_name', 'require', '真实姓名不能为空'],
        ['real_phone', 'require', '真实电话不能为空'],
        ['bank_name', 'require', '开户银行不能为空'],
        ['bank_card_code', 'require', '银行卡号不能为空']
      ];

      $validate_result = $this->validate($data, $rule);
      if($validate_result !== true){
        $this->error($validate_result);
      }

      $realMdl->data($data,true);
      $res = $realMdl->allowField(true)->isUpdate(true)->save();

      if($res){
        $this->success('更新资料成功');
      }else{
        $this->error('更新资料失败，请重试');
      }
    }else{
      $this->error('没有这样的实名信息');
    }
  }

  /**
   * 删除实名信息
   * @param    string                   $id  [description]
   * @param    [type]                   $ids [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-05-04T09:49:44+080
   */
  public function deleteRealName($id='', $ids = []){
    $id = $id ? $id : $ids;
    if($id){
      $this->request->get();
      $realMdl = new UserRealname();
      if($realMdl->destroy($id)){
        adminLog('删除用户实名信息');
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的会员');
    }
  }

  //=========================================================
  //  申请充值

  /**
   * @author: slide
   * @param string $keyword  搜索关键字 用户id
   * @param string $time     申请时间
   * @param int    $page     页码
   *
   */
  public function  applyRechargeList($keyword = '', $time = '', $page = 1){
    $map = [];
    if($keyword){
      $map['user_id'] = ['LIKE', "'{$keyword}'"];
    }
    if($time){
      $map['ap.createtime'] = [['>', strtotime($time)], ['<', (strtotime($time) + 86400000)]];
    }

    $list = Db::name('user_recharge')->alias('ap')->field(['ap.*', 'u.nickname', 'u.phone','wx.nickname as wx_nickname','wx.headimgurl'])->join('__USER__ u', 'u.id=ap.user_id', 'LEFT')->join('__USER_WEICHAT_INFO__ wx','wx.id=u.bindwx','LEFT')->where($map)->order('ap.id desc')->paginate(Config::get('pageSize'), false, ['page' => $page]);
    //dump($list);
    return $this->fetch('apply-recharge-list', ['list' => $list]);
  }

  /**
   * 确认并充值
   * @author: slide
   * @param string $id
   *
   */
  public function recharge_action ($id = '', $status = 0) {
    $template_pay_suc=cache_data( 'site_config' )[ 'template_pay_suc' ];
    if($id){
      if($status == 3){
        $res = Db::name('user_recharge')->where("id", $id)->update(['status' => 3,'note'=>'管理员拒接此次操作', 'paytime'=>time()]);
        if($res){
          $params = ['res' => $res];
          Hook::call('Recharge', 'returnToUser', $params);
          return $this->ajax(2000,'更新成功');
        }else{
          return $this->ajax(4000,'更新失败');
        }
      }
      Db::startTrans();
      try{
        $res = Db::name('user_recharge')->where("id", $id)->update(['status' => 2,'note'=> '管理员充值成功', 'paytime'=>time()]);
        $info = Db::name('user_recharge')->find($id);
        //返钱
        if ($template_pay_suc) {
            $wechat = new Weichat();

            L("测试信息：".$template_pay_suc,'info');

            $template_send_re = $wechat->sendTemplateMsg(WEB_DOMAIN . url( 'home/user/index'),'PAY_SUC',["恭喜您充值成功 ",$info['money'],"后台充值","谢谢您对我们的支持"],$info['user_id']);

              L('cs','info');
            if ( $template_send_re ) {
                Log::write( '充值:模板消息发送成功', 'info');
            } else {
                Log::write( '充值:模板消息发送失败', 'info');
            }
        }

        $result = accountLog($info['user_id'], $info['money'], 1, '充值成功');
        Db::commit();

        if($res && $result){
          $params = ['id' => $id, 'res'=>$res];
          Hook::call('Order', 'confirm', $params);
          return $this->ajax(2000, '操作成功');
        }else{
          return $this->ajax(4000, '操作失败');
        }
      }catch (\Exception $e){
          Db::rollback();
          return $this->error(4000, '操作失败');
      }

    }else{
      $this->error('请选择要操作的记录');
    }
  }

  /**
   * 分成代理
   * @author: slide
   *
   */
  public function getmoney(){
    $res = Db::name('agent_need_getmoney')->select();

    return $this->fetch('getmoney', ['list'=>$res]);
  }

  /**
   * 创建分成代理
   * @author: slide
   *
   */
  public function createagentforgetmoney(){
    $insertData = input('insertJsonData');
    $updateData = input('UpdateJsonData');
    $insertData = json_decode($insertData, true);
    $updateData = json_decode($updateData, true);

    $needMdl = new AgentNeedGetmoney();

    $updateResult = true;
    if(count($updateData) > 0) {
      $updateResult = $needMdl->isUpdate( true )->saveAll( $updateData );
    }

    $insertResult = true;
    if(count($insertData) > 0) {
      $insertResult = $needMdl->isUpdate( false )->saveAll( $insertData );
    }
    if($updateResult && $insertResult){
      $this->success('操作成功');
    }else{
      $this->error('操作失败');
    }
  }

  /**
   * 删除一个或者多个省级代理获取利润
   * @author: slide
   *
   * @param $ids
   *
   */
  public function deleteonegetmoney($ids){
    if($ids){
      if(Db::name('agent_need_getmoney')->delete($ids)){
        return $this->ajax(2000, '删除成功');
      }else{
        return $this->ajax(4000, '删除失败');
      }
    }
  }
}

 ?>
