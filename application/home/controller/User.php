<?php

namespace app\home\controller;

use app\common\Hook;
use think\Controller;
use think\Request;
use app\common\model\User as UserModel;
use app\common\model\UserRealname as UserRealname;
use think\Config;
use think\Db;
use think\Session;
use think\Log;
use app\common\controller\Coupon;
use app\common\controller\TyApi;

use app\api\controller\Sms;
class User extends Validate
{
  protected $userMdl;
  protected function _initialize() {
      parent::_initialize();
      $this->userMdl = new UserModel();
  }
    /**
     * 用户中心
     *
     * @return \think\Response
     */
    public function index()
    {
        // dump(cache('site_config'));
//       dump(getUserFullPath(184));
        $collect_num = Db::name('collect')->where("user_id", Session::get('qt_userId'))->count();
        $serviceCoin = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->find();
        $user_res= $this->userMdl->find(Session::get('qt_userId'));
        //dump($user_res['nickname'] == ''|| $user_res['userimg'] == '');
        if($user_res['bindwx'] != 0 && ($user_res['nickname'] == ''|| $user_res['userimg'] == '')){
          $wx_res = Db::name('user_weichat_info')->find($user_res['bindwx']);
          //dump($wx_res);
          $user_res->nickname = $user_res->nickname  == '' ? urldecode($wx_res['nickname']) : $user_res->nickname;
          $user_res->userimg  = $user_res->userimg   == '' ? $wx_res['headimgurl'] : $user_res->userimg;
        }
        // dump($user_res);
        Session::set('userInfo', $user_res->toArray());

        userGetNotice($user_res['id']);

        // 未处理代理订单数
        $no_read_order = 0;
        if($user_res->agent_type == 1){
          $where = "(user_id={$user_res->id} OR parent_agent={$user_res->id})".Config::get('WAITPAY2');
          $no_read_order = Db::name('order')->where($where)->count();
          //dump(Db::name('order')->getLastSql());
        }
        //dump($no_read_order);
        return $this->fetch('index', [
          'userInfo'=>$user_res->toArray(),
          'collect_num' => $collect_num,
          'no_read_order'=>$no_read_order,
          'serviceCoin' => $serviceCoin
        ]);
    }

    /**
     * 更新用户资料页面
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id = 0)
    {
        $user_res= $this->userMdl->find(Session::get('qt_userId'));
        if($user_res['real_id']){
          $real_status = Db::name('user_realname')->where('user_id', Session::get('qt_userId'))->value('status');
        }else{
          $real_status = -1;
        }
        return $this->fetch('edit', ['userInfo'=>$user_res->toArray(),'real_status'=>$real_status]);
    }

    /**
     * 保存用户信息
     * @param    Response                 $request [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-04-07T14:51:42+080
     */
    public function save($id=0){
      if($this->request->isPost()){
        if(!$id && Session::get('qt_userId') == ''){
          $this->error('没有这样用户', url('login/index'));
        }else{
          $rule = [
            ['oldpassword','require','原密码不能为空'],
            ['password', 'require', '密码不能为空'],
            ['password_confirm','confirm:password', '两次密码不一样'],
          ];
          $data = $this->request->post();
          $user           = $this->userMdl->find($data['id']);
          $user_info = $user;
          if(isset($data['password']) && $data['password'] != ''){
            $validate_result = $this->validate($data, $rule);
            if($validate_result !== true){
              $this->error($validate_result);
            }
            if(md5($data['oldpassword'] . Config::get('user_token')) !== $user->password){
              $this->error('原密码不正确');
            }
            $user->password = md5($data['password'] . Config::get('user_token'));
          }

          $user->id       = $data['id'];
          $user->phone    = isset($data['phone']) ? $data['phone']: $user['phone'];
          $user->email    = isset($data['email']) ? $data['email']: $user['email'];
          $user->sex      = isset($data['sex']) ? $data['sex']: $user['sex'];
          $user->status   = isset($data['status']) ? $data['status'] : $user['status'];
          $user->userimg  = isset($data['userimg']) ? $data['userimg'] : $user['userimg'];
          $user->nickname = isset($data['nickname']) ? $data['nickname'] : $user['nickname'];

          $res = $user->save();
          // dump($user->getLastSql());exit;
          if ($res !== false) {
              Session::set('userInfo', $user_info->toArray());
              $this->success('更新成功');
          } else {
              $this->error('更新失败');
          }
        }
      }else{
        $this->error('访问方式错误');
      }
    }

    /**
     * 用户收藏商品
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-04-07T14:52:14+080
     */
    public function collect($pageSize = 10, $page = 1){
        $userId = Session::get('qt_userId');
        $res = Db::name('collect')->alias('c')->field(['c.collect_id','c.goods_id','g.id','g.title','g.shop_price','g.thumb'])->join("__GOODS__ g",'c.goods_id=g.id')->where("user_id={$userId}")->paginate($pageSize, false, ['page'=>$page]);
        if($this->request->isAjax()){
          return $this->ajax(1001,'数据获取成功','' ,$res);
        }else{
          $this->assign('collect_list', $res);
          return $this->fetch();
        }
    }

    /**
     * 退出
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-20T19:03:20+080
     */
    public function login_out(){
      Session::delete('qt_userId');
      Session::delete('qt_userPhone');
      Session::delete('userInfo');
      cache_data('user'.Session::get('qt_userId'), null);
      Db::name('user')->where('id', Session::get('qt_userId'))->setField('token', '');
      $this->success('退出成功', url('home/login/index'),'', 0);
    }

    /**
     * 我的余额
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-03T10:34:17+080
     */
    public function myremainder(){
      $user_res= $this->userMdl->where('id', Session::get('qt_userId'))->value('user_vb');
      return $this->fetch('myremainder', ['user_vb'=>$user_res]);
    }

  /**
   * 我的钱包
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
    public function mywallet(){
        $user_res= $this->userMdl->where('id', Session::get('qt_userId'))->find();
        $serviceCoin = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->find();
        return $this->fetch('mywallet',['user_vb'=>$user_res['user_vb'],'record'=>$user_res['record'],'order_amount'=>$user_res['order_amount'], 'serviceCoin' => $serviceCoin,'trading_stamp'=>$user_res['trading_stamp']]);
    }

    /**
     * 账户明细
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-03T10:56:35+080
     */
    public function detail($pageSize=10, $page=1)
    {
        $userId = Session::get('qt_userId');
        $data = $this->request->post();
        $where = [];
        if ($userId) {
            $where['user_id'] = $userId;
        }
        if (isset($data['createtime'])) {
            $date = to_sex_month(true);

            foreach ($date as $k => $v) {
                if ($k == intval($data['createtime'])) {
                    $where['createtime'] = [
                        'between',
                        [
                            $v[0],
                            $v[1]
                        ]
                    ];
                }

            }

        }
        $vb_note_res =Db::name('userVbNote')->where($where)->order('id desc')->paginate(Config::get('pageSize'), false, ['page'=>$page]);

        $vb_note_in  =Db::name('userVbNote')->where($where)->where('type',1)->sum('user_money');
        $vb_note_out =Db::name('userVbNote')->where($where)->where('type',2)->sum('user_money');

      if($this->request->isAjax()){
        return $this->ajax(1002, '获取成功', ['list'=>$vb_note_res,'vb_note_in'=>$vb_note_in,'vb_note_out'=>$vb_note_out]);
      }else{
        return $this->fetch('detail', ['list'=>$vb_note_res,'vb_note_in'=>$vb_note_in,'vb_note_out'=>$vb_note_out]);
      }
    }

    /**
   * 账户明细按日期分
   * @methods
   * @desc
   * @author slide
   * @param $type 收入或者支付 1收入2支出
   * @param $from 1、商品购买支出，2、会员转账支出，3、为下级支付订单，4商品分成收入，5推广分成收入6分红补贴收入7团队奖励收入
   */
    public function purchasehis ($type = '', $from = '', $time = '') {
      $where = [];
      if($type) $where['type'] = $type;

      if($from) $where['from'] = $from;

      if($time) {
        $where['createtime'] = ['between', [strtotime(date('Y-m-d 0:0', strtotime($time))), strtotime(date('Y-m-d 23:59', strtotime($time)))]];
      }else{
        $year = thisYear();
        $where['createtime'] = ['between', [$year['start'], $year['end']]];
      }

      $result = Db::name('userVbNote')->where($where)->order('id desc')->select();

      $month = to_sex_month(true);
      $data = [];
      foreach ( $month as $key ) {
        $temp_data = [];
        foreach ($result as $k => $v){
          if(strtotime($v['createtime']) >= intval($key[0]) && strtotime($v['createtime']) < intval($key[1])){
            $temp_data[] = $v;
          }
        }
        if(count($temp_data) > 0) {
          $temp = [
            'date' => date('Y年m月', $key[0]),
            'data' => $temp_data
          ];
          $data[] = $temp;
        }
      }

      return $data;
    }

    /**
     * 充值
     * @return   [type]                   [description]
     * @DateTime :2017-05-04T09:52:25+080
     */
    public function recharge(){
        if (!Session::get('qt_userId')) {
            $this->error("请先登录", "home/login/index");
        }
        $background_color = 'ffffff'; // 背景颜色 可选 默认白色
        $color = "00000"; // 二维码颜色
        $margin = 1; // 二维码间隔 默认1
        $size = 20; // 大小
        $content = WEB_DOMAIN.url('home/ServiceCenter/userRechage?from_id='.Session::get('qt_userId')); // 携带参数
        $opcity = 0; // 背景颜色是否透明
        $params = [
            'background_color'  => $background_color,
            'color'             => $color,
            'margin'            => $margin,
            'size'              => $size,
            'content'           => $content,
            'opcity'            => $opcity,
        ];
        $urlArr = TyApi::instrance('Qrcode.createQrcode', $params, 1)->getUrl();
        return $this->fetch('service_center/qrcode_for_user_get_goods', ['url' => $urlArr['url'].'?'.$urlArr['query']]);

    }

//    public function recharge_back($res = 'topay'){
//  		if($res == 'success') {
//  			$this->success('充值成功',url('home/user/mywallet'));
//  		}
//
//  		if($res == 'fail') $this->error('充值失败', url('home/user/recharge'));
//    }

    /**
     * 创建订单号码
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-13T16:54:42+080
     */
    public function careateTicket(){
      $order_id = '';
      while (true) {
          // 订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
          $order_id_main = date('YmdHis') . rand(10000000, 99999999);
          // 订单号码主体长度
          $order_id_len = strlen($order_id_main);
          $order_id_sum = 0;
          for ($i = 0; $i < $order_id_len; $i ++) {
              $order_id_sum += (int) (substr($order_id_main, $i, 1));
          }
          // 唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
          $order_id = "T" . $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
          return $order_id;
        }
      }

    /**
     * 实名认证信息
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-04T09:23:57+080
     */
    public function realname(){
      $realMdl = new UserRealname();

      if($this->request->isPost()){
        $data = $this->request->post();
        $rule = [
          ['real_name', 'require', '真实姓名不能为空'],
          ['real_phone', 'require', '真实电话不能为空'],
          ['bank_name', 'require', '开户银行不能为空'],
          ['bank_card_code', 'require', '银行卡号不能为空'],
        ];

        $validate_result = $this->validate($data, $rule);
        if($validate_result !== true){
          $this->error($validate_result);
        }
        $data['status'] = 0;
        $data['user_id'] = Session::get('qt_userId');

        $realMdl->data($data, true);
        if(input('id')){
          $res = $realMdl->allowField(true)->isUpdate(true)->save();
        }else{
          $res = $realMdl->allowField(true)->save();
        }
        // dump($realMdl->getLastSql());exit;
        if($res){
          $this->userMdl->where("id", Session::get('qt_userId'))->setField('real_id', $realMdl->id);
          $this->success('提交资料成功',url('home/user/index'),'',1);
        }else{
          $this->error('提交资料失败，请重试');
        }
      }

      $real_res = $realMdl->where("user_id", Session::get('qt_userId'))->find();

      if($real_res){
        $this->assign('real_info', $real_res);
      }

      return $this->fetch();
    }

    /**
     * 申请成为代理
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-15T09:51:51+080
     */
    public function applyAgent(){
      if($this->request->isPost()){
        $data = $this->request->post();
        if(isset($data) && $data['agent_type'] != '' && $data['money'] != '' && $data['user_id'] != ''){
          if(Db::name('agentApply')->where('user_id', $data['user_id'])->where(['agent_type'=> $data['agent_type'],'status'=>0])->find()){
            $this->error('您的申请正在处理，请耐心等候~~');
          }
          $data['createtime'] = time();
          /*if(Session::get('userInfo')['user_vb'] < $data['money']){
            $this->error('余额不足，无法申请，请充值后重试');
          }*/
          $data['ticket'] = careateTicket();
          if(Db::name('agentApply')->insert($data)){
            $apply_id = Db::name('agentApply')->getLastInsID();
            $params = ['order_id' => 0,'id' => $apply_id];
            Hook::call('Agent', 'addSuccess', $params);
            // 发送消息模板消息给客服
            $wechat           = new Weichat();
            $kefu_phone = Config::get('shipping_kefu_account');
            $kefu_uid = getUserInfoBywhere(['phone'=>$kefu_phone], 'id');
            if($kefu_uid) {
              $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
                '亲爱的 财务专员商城有一条新的代理申请需要处理!','代理申请','待办','请尽快处理！'
              ], $kefu_uid['id']);
              if ( $template_send_kefu_res ) {
                Log::write( '申请用户:' .$data['user_id'] . '模板消息发送成功', 'info' );
              } else {
                Log::write( '申请用户:' . $data['user_id'] . '模板消息发送失败', 'info' );
              }
            }
            $this->success('申请代理成功,等待审核');
          }else{
            $this->error('申请代理失败,请稍后重试');
          }
        }else{
          $this->error('缺少必要参数');
        }
      }else{
        //$this->error('访问方式错误');
        $res = Db::name('agent')->select();
        $apply_last = Db::name('agent_apply')->where(['user_id' => Session::get('qt_userId'), 'status' => 1])->order('createtime DESC')->sum('money');

        $user_res = Db::name('user')->find(Session::get('qt_userId'));
        $this->assign('userInfo', $user_res);
        $this->assign('agent', $res);
        $this->assign('apply_last', $apply_last);

        return $this->fetch();
      }
    }

  /**
   * 新代理申请（在线支付）
   * @methods
   * @desc
   * @author slide
   *
   */
    public function applyAgentNew(){
      $data = $this->request->post();

      if(isset($data) && $data['agent_type'] != '' && $data['money'] != '' && $data['user_id'] != ''){
        if(Db::name('agentApply')->where('user_id', $data['user_id'])->where(['agent_type'=> $data['agent_type'],'status'=>0])->find()){
          //$this->error('您的申请正在处理，请耐心等候~~');
          //关闭之前的申请
          Db::name('agentApply')->where(['agent_type'=> $data['agent_type'],'status'=>0, 'user_id'=>$data['user_id']])->update(['status' => 3]);
        }
        $data['createtime'] = time();
        /*if(Session::get('userInfo')['user_vb'] < $data['money']){
          $this->error('余额不足，无法申请，请充值后重试');
        }*/
        $data['ticket'] = careateTicket();
        if(Db::name('agentApply')->insert($data)){
          $apply_id = Db::name('agentApply')->getLastInsID();
          $params = ['order_id' => 0,'id' => $apply_id];
          Hook::call('Agent', 'addSuccess', $params);
          // 发送消息模板消息给客服
          $wechat           = new Weichat();
          $kefu_phone = Config::get('shipping_kefu_account');
          $kefu_uid = getUserInfoBywhere2(['phone'=>$kefu_phone], 'id');
          if($kefu_uid) {
            $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
              '亲爱的 财务专员商城有一条新的代理申请需要处理!','代理申请','待办','请尽快处理！'
            ], $kefu_uid['id']);
            if ( $template_send_kefu_res ) {
              Log::write( '申请用户:' .$data['user_id'] . '模板消息发送成功', 'info' );
            } else {
              Log::write( '申请用户:' . $data['user_id'] . '模板消息发送失败', 'info' );
            }
          }
          $this->success('申请代理成功,等待审核', '', ['last_id' => $apply_id]);
        }else{
          $this->error('申请代理失败,请稍后重试');
        }
      }else{
        $this->error('缺少必要参数');
      }

    }

    /**
   * 申请代理列表
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
    public function applyAgentList(){
      $list = Db::name('agentApply')->where('user_id', Session::get('qt_userId'))->select();

      return $this->fetch('apply_agent_list', ['list' => $list]);
    }



    /**
   * 申请充值
   * @author: slide
   * @return mixed
   */
    public function applyRecharge(){
      if($this->request->isPost()){
        $data = $this->request->post();
        if(!isset($data['money']) || $data['money'] == 0 || $data['money'] == '') $this->error('请选择金额');
        $data['user_id'] = Session::get('qt_userId');
        $data['createtime'] = time();
        $data['note'] = '申请成功,等待付款！';
        $data['ticket'] = careateTicket();
        $data['status'] = 1;

        $result = Db::name('user_recharge')->insert($data);
        $last_id = Db::name('user_recharge')->getLastInsID();
        if($result){
          $params = ['id' => $last_id, 'userId' => Session::get('qt_userId')];
          Hook::call('Recharge', 'addSuccess', $params);

          // 发送消息模板消息给客服
          /*$wechat           = new Weichat();
          $kefu_uid = getUserInfoBywhere('phone', Config::get('shipping_kefu_account'));
          $template_send_kefu_res = $wechat->sendTemplateMsg('','TASK_PROCESSING',[
            '亲爱的 财务专员商城有一条新的充值申请需要处理!','充值申请','待办','请尽快处理！'
          ], $kefu_uid['id']);
          if ( $template_send_kefu_res ) {
            Log::write( '申请用户:' .$data['user_id'] . '模板消息发送成功', 'info' );
          } else {
            Log::write( '申请用户:' . $data['user_id'] . '模板消息发送失败', 'info' );
          }*/
          $this->success('申请成功，正在进入支付...','', $last_id);
        }else{
          $this->error('申请失败，请重试');
        }

      }else{

        return $this->fetch();
      }
    }

    /**
     * 我的团队（联系人）
     * @author: slide
     * @param int $type 查询类型 0普通会员 1区域代理 2市级代理 3县级代理 4经销商
     * @param  string $keyword 昵称|电话号码|id
     */
    public function MyTeam($keyword='', $type = '', $pagesize = 10, $page = 1){
        if($this->request->isPost()){
          if(isset($type)){
            //确认自己的代理等级
            $self_agent_type = Session::get('userInfo')['agent_type'];
            //校验
            if($self_agent_type == 0 && $type != 0){
              $type = 0;
            }
            $where['agent_type'] = $type;
            if($keyword){
              $where['keyword'] = $keyword;
            }
            //根据类型查询下级的代理
            $res = $this->userMdl->getUserChild(Session::get('qt_userId'), $where);

            return $this->ajax(2000, '查询结果', '', ['list' => $res, 'count' => count($res)]);
          }else{
            $this->error('缺少必要参数');
          }
        } else {
          return $this->fetch();
        }
      }



    /**
     * 我的团队（联系人）
     * @author: slide
     * @param int
     * $
     */
    public function MyTeams( $phone='', $nickname='',$pagesize = 10, $page = 1){
        $pid=Db::name('user')->where('id',Session::get('qt_userId'))->value('pid');
          $where=[];
        if($pid){
            $where['pid']=$pid;
        }
        if(isset($phone)){
            $where['phone']=$phone;
        }

        if(isset($nickname)){
            $where['nickname']=$nickname;
        }
        $result_myteam=Db::name('user')->where($where)->paginate(Config::get('pageSize'), false, ['page'=>$page]);
        if($result_myteam){
            if($this->request->isAjax()){
               return $this->ajax('2000','成功！','',$result_myteam);
            }else{
                return $this->success('成功！');
            }
         }else{
            if($this->request->isAjax()){
                return $this->ajax('4000','失败！','',$result_myteam);
            }else{
                return $this->success('失败！');
            }
        }




    }

    /**
   * 推广中心
   * @author: slide
   *
   */
    public function popularize(){
    $uid = input('uid');
    $this->assign('uid', $uid);
    return $this->fetch();
  }

    /**
     * 解绑微信
     * @author: slide
     */
    public function unbindweichat(){
      $user_info = $this->userMdl->find(Session::get('qt_userId'));
      if($user_info->bindwx == 0){
        $this->error('您还没有绑定微信，无需解绑');
      }else{
        Db::startTrans();
        try{
          Db::name('user_weichat_info')->delete($user_info->bindwx);
          $this->userMdl->where('id', $user_info->id)->setField('bindwx', 0);
          Db::commit();
          return $this->ajax(2000, '解绑微信成功');
        }catch (\Exception $e){
          Db::rollback();
          return $this->ajax(4000, '解绑失败');
        }

      }
    }

    /**
     * 手机端代理后台
     * @author: slide
     *
     */
    public function firstagentadmin(){
      return $this->fetch();
    }

    /**
     * 会员点对点转账
     * @author: slide
     *
     */
    public function transfer_account(){
      //$from_user = input('from_user', 0); // 发起人
      $to_user = input('to_user', 0); // 收款人
      $to_user_phone = input('to_user_phone', 0);
      $money = input('money', 0); // 款项
      $password = input('password');

      if($to_user) {
        $towh['u.id'] = $to_user;
      }else{
        $towh['u.phone'] = $to_user_phone;
      }
      if((!$to_user && !$to_user_phone) || !$money || !$password){
        return $this->ajax(4000,'缺少必要的参数，无法完成该动作');
      }

      // 检测密码
      $from_user_password = Session::get('userInfo')['pay_password'];
      if(md5(md5(Config::get('PAY_TOKEN')).$password) != $from_user_password){
        return $this->ajax(4000, '密码验证错误');
      }
      $from_user = Session::get('userInfo');
      $from_user_vb = model('user')->where('id', Session::get('userInfo')['id'])->value('user_vb');
      if($from_user_vb < $money){
        return $this->ajax(4000, '余额不足');
      }
      $to_user_res = model('user')->alias('u')->field(['u.nickname','u.phone','u.id','wx.nickname as wx_nickname'])->join('__USER_WEICHAT_INFO__ wx', 'u.bindwx=wx.id', 'left')->where($towh)->find();
      //dump($to_user_res);exit;
      $to_user = $to_user ? $to_user : $to_user_res['id'];
      // 转账动作
      Db::startTrans();
      try{

        $transfer_account_data = [
          'from_user_nickname' => is_null($from_user['nickname'])?'':$from_user['nickname'],
          'from_user_phone' => $from_user['phone'],
          'from_user_id'  => $from_user['id'],
          'to_user_nickname' => is_null($to_user_res['wx_nickname'])?'':$to_user_res['wx_nickname'],
          'to_user_phone' => $to_user_res['phone'],
          'to_user_id'    => $to_user,
          'money'         => $money,
          'note'          => input('note','', 'string'),
          'createtime'    => time()
        ];
        // 减少来源账户
        accountLog($from_user['id'],$money, 2, '向朋友转账支出');

        // 增加来源账户
        accountLog($to_user, $money, 1, '收到朋友转账收入');

        // 增加记录
        Db::name('transfer_accounts')->insert($transfer_account_data);
        Db::commit();
        $user = model('user')->find($from_user['id']);
        Session::set('userInfo', $user->toArray());
        return $this->ajax(2000, '转账成功');
      }catch (\Exception $e){

        L('转账错误'.$e);
        Db::rollback();
        return $this->ajax(4000, '转账失败');
      }
    }

    /**
     * 转账前台页面
     * @author slide
     * @return mixed
     *
     */
    public function transfer () {



      return $this->fetch();
    }

    /**
     * 提现记录
     * @desc
     * @author slide
     *
     */
    public function withdrawrows($page = 1){
      $where['userid'] = Session::get('qt_userId');
      $list = \app\common\model\Usersqtx::where($where)->order('id', 'desc')->paginate(10, false, ['page' => $page]);

      return $this->fetch('withdrawrows', ['list' => $list]);
    }

    /**
     *
     * 提现记录详情
     *
     */

    public  function  widthdraw_detail($id=''){

        $result=Db::name('usersqtx')->alias('a')->field(['a.*','b.real_name','b.bank_card_code','b.bank_name'])->join('__USER_REALNAME__ b','a.userid=b.id','left')->where('a.id',$id)->find();
        if($this->request->isAjax()){

            return $this->ajax('2000','成功！','',$result);
              }else{

                  $this->assign('data',$result);
                  return $this->fetch();
        }



    }

    /**
     * 佣金记录
     * @methods
     * @desc
     * @author slide
     */
    public function commission () {
      $result = Db::name('divide_note')->where('user_id', Session::get('qt_userId'))->order('id', 'desc')->select();

    }

    /**
     * 创建/修改支付密码
     * @author: slide
     */
    public function change_paypassword($secen = 1){
      $paypassword = input('paypassword','','string');
      $paypassword_confirm = input('paypassword_confirm','','string');
      $uid = Session::get('qt_userId');
      if(!$paypassword || $paypassword == '' || !$paypassword_confirm || $paypassword_confirm == ''){
        $this->error('不能为空');
      }
      if($paypassword_confirm != $paypassword){
        $this->error('两次密码不一样');
      }
      //旧密码
      if($secen == 1){
        $user_pay_password = $this->userMdl->where('id', $uid)->value('pay_password');
        $oldpassword = input('old_password', '', 'string');
        if(md5(md5(Config::get('PAY_TOKEN')).$oldpassword) != $user_pay_password){
          $this->error('原密码错误');
        }
      }
      $data['pay_password'] = md5(md5(Config::get('PAY_TOKEN')).$paypassword);

      if($this->userMdl->where('id',$uid)->update($data)){
        $user_res = $this->userMdl->find($uid);
        Session::set('userInfo',$user_res->toArray());
        $this->success('支付密码设置成功');
      }else{
        $this->error('支付密码设置失败');
      }
    }

    /**
     * 支付密码
     * @author: slide
     *
     */
    public function checkpaypassword(){
      return $this->fetch();
    }
    /**
     * 验证支付密码
     * @author: slide
     *
     */
    function check_password($password = ''){
      $uid = Session::get('qt_userId');
      $user_pay_password = $this->userMdl->where('id', $uid)->value('pay_password');
      if(md5(md5(Config::get('PAY_TOKEN')).$password) != $user_pay_password){
        $this->error('支付密码错误');
      } else {
        $this->success('支付密码正确');
      }
    }
    /**
     * 找回支付面
     * @author: slide
     * @return mixed|void
     *
     */
    public function findpaypassword(){
    if($this->request->isPost()){
      $data = $this->request->post();
      $rule = [
        ['phone', 'require', '电话不能为空'],
        ['code', 'require', '验证码不能为空'],
        ['pay_password', 'require|confirm', '密码不能为空|两次密码不一样']  // password_confirm
      ];
      $result = $this->validate($data, $rule);
      if(true !== $result) $this->error($result);
      $code = input('post.code');
      if(strtolower($code) !== strtolower(Session::get('sms_code'))){
        $this->error('验证码错误');
      }
      $user = $this->userMdl->where('phone', $data['phone'])->find();
      if(!$user) return $this->error('没有这样的用户');
      // 重置密码
      $user_data['pay_password'] = md5(md5(Config::get('PAY_TOKEN')).$data['pay_password']);

      $res = $this->userMdl->where("phone", $data['phone'])->update($user_data);

      if($res){
        $this->success('重置成功，现在就去登录吧', url('home/login/index'));
      }else{
        $this->error('重置失败，请重试', url('home/login/FindPassword'));
      }
    }else {
      return $this->fetch();
    }
  }

    // 积分页面;
    public function point(){

      return $this->fetch('point');
    }

    // 积分转余额
    public function point_to_remainder(){
      return $this->fetch('point_to_remainder');
    }

    public function point_to_goods(){
      return $this->fetch('point_to_goods');
    }
    // 赠品券
    public function coupon(){

      return $this->fetch('coupon');
    }
    // 我的业绩
    public function myachive(){
      return $this->fetch('myachive');
    }
    // 销售业绩
    public function sales_achive(){
      return $this->fetch('sales_achive');
    }
    // 绑定银行卡
    public function bind_credit_card(){
      return $this->fetch('bind_credit_card');
    }
    // 绑定详情
    public function bind_detail(){
      return $this->fetch('bind_detail');
    }

    public function opening_order(){
      return $this -> fetch();
    }

    /**
     * 充值小账本
     * @return mixed|\think\response\Json
     */
    public  function   recharge_detail_in($page){
        $where=[];

        $user_id=Session::get("qt_userId");
        if($user_id){
            $where['a.user_id']=$user_id;
        }
        $datas=[];
        $total = 0;
        $date=to_sex_month(true);
        $time=$date[0];
        $times=$date[11];
        $rs=Db::name('user_recharge')->alias('a')->field(['a.*,b.phone'])->join("__USER__ b",'a.user_id=b.id','left')->where($where)->where('a.status',2)->where('a.createtime','between',[$time[0],$times[1]])->where('a.status',2)->paginate(Config::get('pageSize'), false, ['page'=>$page]);
        for ($i=0;$i<count($date);$i++){
            $tims=$date[$i];
            for ($j=0;$j<count($rs);$j++){
                $dats=$rs[$j];
                if($tims[0]>$dats['createtime']&&$dats['createtime']<$tims[1]){
                    $datas[$i]=$rs[$j];
                    $total += intval($rs[$j]['money']);
                }
            }
        }
        if($this->request->isAjax()){

            return $this->ajax('2000','明细查询成功','',$datas);

        }else{
            return $this -> fetch('', ['result' => $datas, 'total' => $total]);

        }



    }

    //充值明细
    public function recharge_details($page=1){
        $where=[];
        $data=$this->request->post();
        $user_id=Session::get("qt_userId");
        if($user_id){
            $where['a.user_id']=$user_id;
        }

        $date=to_sex_month(true);
         $time=$date[0];
         $times=$date[11];

         $rs=Db::name('user_recharge')->alias('a')->field(['a.*,b.phone'])->join("__USER__ b",'a.user_id=b.id','left')->where($where)->where('a.status',2)->where('a.createtime','between',[$time[0],$times[1]])->paginate(Config::get('pageSize'), false, ['page'=>$page]);

        $result = [];
        foreach ($date as $k => $v) {
            $temp_data = [];

            foreach ($rs as $key => $val) {
                $val_time = $val['createtime'];
                if ($val_time >= intval($v[0]) && $val_time < intval($v[1])) {
                    $temp_data[] = $val;

                }
            }
            $result[] = $temp_data;
        }
        $result_recharge_in=Db::name('user_recharge')->alias('a')->where($where)->where('status',2)->sum('money');
        if($this->request->isAjax()){
            $data = [
                'recharge'=>$result,
                'recharge_in'=>$result_recharge_in
            ];
           return $this->ajax('2000','明细查询成功','',$data);

          }else{
              return $this -> fetch();

          }

    }


  public function order_goods(){
    return $this -> fetch();
  }

}
