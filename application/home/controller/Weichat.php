<?php
namespace app\home\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Log;
use think\Session;
use think\Config;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order as EasyOrder;
use EasyWeChat\Message\News;
use \Firebase\JWT\JWT;

use app\common\model\User as UserModel;
use app\common\model\Order as OrderModel;

/**
 * 微信支付类
 */
class Weichat extends Controller {
  protected $config;
  protected $options;
  public    $easy_app;
  protected $easy_payment;

  //初始化操作
  protected function _initialize () {
    parent::_initialize();
    $weichat_db_config = Db::name( 'weichat_info' )->field( [ 'appid', 'appsecret', 'token', 'pay_id', 'pay_key' ] )->find( 1 );
    $payment_db_config = Db::name( 'payment' )->field( [ 'config' ] )->where( "paycode='Weichat'" )->find()[ 'config' ];

    $payment_config_arr = [];
    if ( $payment_db_config ) {
      $payment_config = explode( '|$|', $payment_db_config );
      foreach ( $payment_config as $key => &$value ) {
        $value = json_decode( $value, true );
        foreach ( $value as $k => $v ) {
          $payment_config_arr[ $k ] = $v;
        }
      }
    }
    // dump($payment_config_arr);exit;
    $this->config = array_merge( $weichat_db_config, $payment_config_arr );
    // dump($this->config);exit;
    //easy weichat options
    \think\Log::write( "微信配置" . json_encode( $this->config ), 'error' );
    $this->options      = [ /**
     * Debug 模式，bool 值：true/false
     * 当值为 false 时，所有的日志都不会记录
     */
      'debug'   => true, /**
       * 账号基本信息，请从微信公众平台/开放平台获取
       */
      'app_id'  => $this->config[ 'appid' ],         // AppID
      'secret'  => $this->config[ 'appsecret' ],     // AppSecret
      'token'   => $this->config[ 'token' ],          // Token
      'aes_key' => 'aT8nXO2xmgpBOKWhtcoU8xHXfEPl3wJcCrvS4HYn2bs',                    // EncodingAESKey，安全模式下请一定要填写！！！
      /**
       * 日志配置
       * level: 日志级别, 可选为：
       *         debug/info/notice/warning/error/critical/alert/emergency
       * permission：日志文件权限(可选)，默认为null（若为null值,monolog会取0644）
       * file：日志文件位置(绝对路径!!!)，要求可写权限
       */
      'log'     => [ 'level' => 'debug', 'permission' => 0777, 'file' => IS_WIN ? 'D:/www/liquorMall/runtime/log/easywechat.log' : '/yjdata/www/moMall/runtime/log/easywechat.log'], /**
       * OAuth 配置
       * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
       * callback：OAuth授权完成后的回调页地址
       */
      'oauth'   => [ 'scopes' => [ 'snsapi_userinfo' ], 'callback' => '/home/Weichat/auth_callback' ],
      /**
       * 微信支付
       */
      'payment' => [ 'merchant_id' => $this->config[ 'pay_id' ], 'key' => $this->config[ 'pay_key' ], // 'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
        // 'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
        // 'device_info'     => '013467007045764',
        // 'sub_app_id'      => '',
        // 'sub_merchant_id' => '',
        // ...
                     'notify_url'  => Config::get( 'WX_NOTIFY' ) ],
      /**
       * Guzzle 全局设置
       * 更多请参考： http://docs.guzzlephp.org/en/latest/request-options.html
       */
      'guzzle'  => [ 'timeout' => 3.0, // 超时时间（秒）
                     'verify'  => false, // 关掉 SSL 认证（强烈不建议！！！）
      ], ];
    $this->easy_app     = new Application( $this->options );
    $this->easy_payment = $this->easy_app->payment;
  }

  /**
   * 微信介入
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-29T16:35:52+080
   */
  public function index () {
	  \Think\Log::write('wechat begin', 'error');
    //获得参数 signature nonce token timestamp echostr
    $nonce     = $_GET[ 'nonce' ];
    $token     = $this->options[ 'token' ];
    $timestamp = $_GET[ 'timestamp' ];
    $echostr   = isset( $_GET[ 'echostr' ] ) ? $_GET[ 'echostr' ] : "";
    $signature = $_GET[ 'signature' ];
    //形成数组，然后按字典序排序
    $array = [];
    $array = [ $nonce, $timestamp, $token ];
    sort( $array );
    //拼接成字符串,sha1加密 ，然后与signature进行校验
    $str = sha1( implode( $array ) );
    if ( $str == $signature && $echostr ) {
      //第一次接入weixin api接口的时候
      echo $echostr;
      exit;
    } else {
	  \Think\Log::write('wechat begin', 'error');
      $this->getMsgFromWechat();
    }
  }

  /**
   * 拉取授权
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-17T10:01:02+080
   */
  public function auth_login () {
    $oauth = $this->easy_app->oauth;
    // 绑定
    if ( empty( $_SESSION[ 'wechat_user' ] ) ) {
      $_SESSION[ 'target_url' ] = $_SERVER[ 'REQUEST_URI' ];
      $regster_bindwx = $this->request->get('regster_bindwx');
      $regster_uid = $this->request->get('regster_uid');
      $registerBindWx = $regster_bindwx ? $regster_bindwx : false;
      Session::set('register_bindwx',$registerBindWx );
      $registerUid = $regster_uid ? $regster_uid : 0;
      Session::set('regster_uid', $registerUid);
      $tjr_uid = input('uid') ? input('uid') : '';
      Session::set('tjr_uid', $tjr_uid);
      //dump($_SESSION);exit;
      // dump($_SERVER);
      $oauth->redirect()->send();
      exit;
      // return $oauth->redirect();
      // 这里不一定是return，如果你的框架action不是返回内容的话你就得使用
      // $oauth->redirect()->send();
    }
    // 已经绑定过
    $user = $_SESSION[ 'wechat_user' ];
  }

  /**
   * 登录回调
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-17T10:17:06+080
   */
  public function auth_callback () {
    $oauth = $this->easy_app->oauth;
    // 获取 OAuth 授权结果用户信息
    $user = $oauth->user();
    Session::set( 'wechat_user', $user->toArray() );
    $targetUrl        = empty( $_SESSION[ 'target_url' ] ) ? '/' : $_SESSION[ 'target_url' ];
    $userInfo         = $user->toArray();
    $weichat_info     = Db::name( 'user_weichatInfo' );
    $weichat_info_res = $weichat_info->where( "openid='" . $userInfo[ 'original' ][ 'openid' ] . "'" )->find();
    // dump($_SERVER);
    // exit;
    if ( $weichat_info_res ) {
      //直接登录
      $user      = new UserModel();
      //dump($weichat_info_res);exit;
      $user_info = $user->where( "bindwx=" . $weichat_info_res[ 'id' ] )->find();
      if(!$user_info){
        $this->error('此账号还没有绑定微信号，但是微信号已经存在，请联系管理员');
      }
      Session::set( 'qt_userId', $user_info[ 'id' ] );
      Session::set( 'qt_userPhone', $user_info[ 'phone' ] );
      Session::set( 'userInfo', $user_info->toArray() );
      userGetNotice($user_info['id']); // 获取公告信息

      $this->success( '登录成功', url( 'index/index' ), $user->toArray(),0 );
    } else {
      if ( Session::get( 'register_bindwx' ) == true ) {
        $userMdl = new UserModel();
        L('wxSession', var_export($_SESSION, true));
        if ( !Session::get( 'regster_uid' ) || !Session::get( 'regster_uid' ) ) {
          return $this->error( '绑定失败', url( 'home/index/index' ) );
        }
        $user_res = $userMdl->find( Session::get( 'regster_uid' ) );
        L( '绑定用户信息' . json_encode( $user_res ) );
        if ( $user_res ) {
          $this->doLogin( $user_res );
          userGetNotice($user_res['id']); // 获取公告信息

          //插入微信表
          $session_user = Session::get( 'wechat_user' )[ 'original' ];
          $user_weichat = [
            'nickname'    => urlencode($session_user[ 'nickname' ]),
            'openid'      => $session_user[ 'openid' ],
            'sex'         => $session_user[ 'sex' ],
            'city'        => $session_user[ 'city' ],
            'province'    => $session_user[ 'province' ],
            'country'     => $session_user[ 'country' ],
            'headimgurl'  => $session_user[ 'headimgurl' ],
            'createtime'  => time()
          ];
          $id           = $this->addUserWeichat( $user_weichat );
          $userMdl->where( 'id', $user_res[ 'id' ] )->setField( 'bindwx', $id );
          $this->success( '绑定成功', url( 'home/index/index' ), $user_res->toArray(), 0);
        } else {
          $this->error( '绑定失败', url( 'home/index/index' ), '', 0 );
        }
      } else {
        $this->redirect( 'home/weichat/weichat2site' );
      }
    }
    // header('location:'. $targetUrl); // 跳转到 user/profile
  }

  /**
   * 绑定账号
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-17T10:46:01+080
   */
  public function weichat2site () {
    $userMdl = new UserModel();
    if($this->request->isPost()){
      $data = $this->request->post();
      if(!isset($data['phone']) || $data['phone'] == ''){
        $this->error('电话号码不能为空');
      }
      //验证
      $user_res = $userMdl->where("phone='".$data['phone']."'")->find();
      if($user_res){
        //有记录则做登录，
        if(md5($data['password'] . Config::get('user_token')) != $user_res['password']){
          $this->error('用户密码错误');
        }else{
          //登录成功
		      \Think\Log::write('userInfo'.$user_res, 'info');
          $this->doLogin($user_res);
          userGetNotice($user_res['id']); // 获取公告信息

          //插入微信表
          $session_user = Session::get('wechat_user')['original'];
          $user_weichat = [
            'nickname'    =>  urlencode($session_user['nickname']),
            'openid'      =>  $session_user['openid'],
            'sex'         =>  $session_user['sex'],
            'city'        =>  $session_user['city'],
            'province'    =>  $session_user['province'],
            'country'     =>  $session_user['country'],
            'headimgurl'  =>  $session_user['headimgurl'],
            'createtime'  =>  time()
          ];
          $id = $this->addUserWeichat($user_weichat);
          $userMdl->where('id', $user_res['id'])->setField('bindwx', $id);
          $this->success('绑定成功', url('user/index'), $user_res->toArray(), 0);
        }
      }else{
        //无记录则做注册并登录
        $validate_result = $this->validate($data, 'User');
        if(!$validate_result){
          $this->error($validate_result);
        }else{
          $code = input('post.code');
          if(strtolower($code) !== strtolower(Session::get('sms_code'))){
            $this->error('验证码错误');
          }else{
            $data['password'] = md5($data['password'] . Config::get('user_token'));
            //处理上下级
            if(isset($data['agent_phone']) && $data['agent_phone'] != ''){
              //填了推荐人号码
              $agent_user_info = $userMdl->where('phone', $data['agent_phone'])->find();
              if($agent_user_info){
                $uid = $agent_user_info['id'];
              }else{
                return $this->error('您填入的推荐人不存在');
              }
            }else{
              if($_SESSION['tjr_uid'] != '' &&Db::name('user')->find($_SESSION['tjr_uid'])){
                //有推荐人
                $uid = $_SESSION['tjr_uid']; //推荐人
              }else{
                //无推荐人,进入公司账户, 后期用于可调节
                $uid = $userMdl->where('is_employ_agent', 1)->value('id');
              }
            }

            $parent_user_info = $userMdl->getUserInfo($uid);
            $data['pid'] = $uid;
            //dump($parent_user_info);
            if($parent_user_info){
              if($parent_user_info->agent_type == 1){
                $data['parent_agent'] = $uid;
              }else{
                $data['parent_agent'] = $parent_user_info->parent_agent;
              }
            }
            $userMdl->data($data, true);
            $register_res = $userMdl->allowField(true)->save();
            if ($register_res) {
              //插入微信表
              $session_user = Session::get('wechat_user')['original'];
              $user_weichat = [
                'nickname'    =>  $session_user['nickname'],
                'openid'      =>  $session_user['openid'],
                'sex'         =>  $session_user['sex'],
                'city'        =>  $session_user['city'],
                'province'    =>  $session_user['province'],
                'country'     =>  $session_user['country'],
                'headimgurl'  =>  $session_user['headimgurl'],
                'createtime'  =>  time()
              ];
              $id = $this->addUserWeichat($user_weichat);
              $userMdl->where('id', $userMdl->id)->setField('bindwx', $id);
              $register_id = $userMdl->id;
              // 给上一级用户发一条系统信息
              $phone = substr($userMdl->phone, 0, 3) . '****'.substr($userMdl->phone, -1, 4);
              $message = new \app\api\controller\Message();
              $message->sendMsg('您推荐（'.$phone.'）成为商城会员，感谢您的支持！',date('Y-m-d H:i')."有一名会员（{$session_user['nickname']}）会员成功注册商城，请尽快联系会员 ", -1, $uid, 3, false);

              // 上级用户直接给当前用户发消息
              $message->sendMsg('','欢迎注册【'.Config::get('sms_sign')."】,我将是您的服务商，有任何需要可以联系我!", $uid, $register_id, 1, false);
              $user_res = $userMdl->find($register_id);
              $this->doLogin($user_res);
              userGetNotice($user_res['id']); // 获取公告信息

//              $this->success('注册成功', url('home/index/index'), 0);
              $this->redirect( url('home/index/index'));
            } else {
                $this->error('注册失败', '','', 0);
            }
          }
        }
      }
    }else{
      // dump(Session::get('wechat_user'));
      $weichat_user = Session::get('wechat_user')['original'];
      $res = Db::name('userWeichatInfo')->where("openid", $weichat_user['openid'])->find();

      if($res){
        $user_res = Db::name('user')->where("id='".$res['user_id']."'")->find();
        //登录成功
        $this->doLogin($user_res);
        $this->success('登录成功', url('index/index'), $user_res->toArray());
      }else{
        //dump($weichat_user);
        $this->assign('nickname', $weichat_user['nickname']);
        $this->assign('userimg', $weichat_user['headimgurl']);
        $uid = Session::get('tjr_uid') ? Session::get('tjr_uid') : '';
        $this->assign('tjr_uid', $uid);
        return $this->fetch('login/bindwx/index');
      }
    }
  }

  /**
   * 登录动作
   * @author: slide
   * @param $user_res
   *
   */
  public function doLogin($user_res){
    Session::set('qt_userId', $user_res['id']);
    Session::set('qt_userPhone', $user_res['phone']);
    Session::set('userInfo', $user_res->toArray());
    $token = array(
      "iss" => WEB_DOMAIN,
      'userId' => $user_res['id'],
      'userPhone' => $user_res['phone'],
      "nbf" => time()
    );
    $jwt = JWT::encode($token, Config::get('user_login_key'));
    Db::name('user')->where("id", $user_res['id'])->setField('token', $jwt);
    $user_res->token = $jwt;
    \Think\Log::write('userInfo'.$user_res, 'info');
    cache_data('user'.$user_res['id'], $user_res->toArray());
  }

  /**
   * 微信表插入
   *
   * @Author   :  slade
   * @DateTime :2017-04-17T10:56:37+080
   */
  public function addUserWeichat ( $data ) {
    $weichat   = Db::name( 'user_weichatInfo' )->insert( $data );
    $insert_id = Db::name( 'user_weichatInfo' )->getLastInsID();

    return $weichat ? $insert_id : false;
  }

  /**
   * 唤起支付的动作
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-17T09:35:09+080
   */
  public function toPay ( $orderId = '', $backurl = '', $order_type="goods" ) {
    if ( !$orderId ) {
      $this->error( '缺少必要参数', url( 'home/user/index' ) );
    }

    if ( !isset(Session::get('userInfo')['bindwx']) ) {
      $this->error( '请先绑定微信' );
    }
    $user_weichat_info = Db::name('user_weichat_info')->find(Session::get('userInfo')['bindwx']);
    // dump($user_weichat_info);

    if(!$user_weichat_info) {
      $this->error( '请先绑定微信', url('home/user/edit') );
    }
    if ( $order_type == 'goods' ) {
      //查询订单信息
      $orderMdl   = new OrderModel();
      $order_res  = $orderMdl->find( $orderId );
      $attributes = [ 'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
                      'body'       => '[' . Config::get( 'SMS_SIGN' ) . ']订单-' . $order_res[ 'order_sn' ],
                      'detail' => '[' . Config::get( 'SMS_SIGN' ) . ']订单-' . $order_res[ 'order_sn' ] . '支付',
                      'out_trade_no' => $order_res[ 'order_sn' ],
                      'total_fee' => $order_res['total_amount'] * 100, // 单位：分
                      'openid'     => $user_weichat_info['openid'],
                      'attach' => "ordertype={$order_type}"
                    ];
    } else if ( $order_type == 'recharge' ) {
      $real_res = Db::name( 'user_recharge' )->find( $orderId );
      \Think\Log::write( '充值信息' . json_encode( $real_res ) );
      $attributes = [ 'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
                      'body'       => '[' . Config::get( 'SMS_SIGN' ) . ']充值-' . $real_res[ 'ticket' ],
                      'detail'     => '[' . Config::get( 'SMS_SIGN' ) . ']充值-' . $real_res[ 'ticket' ] . '支付',
                      'out_trade_no' => $real_res[ 'ticket' ],
                      'total_fee' => $real_res['money'] * 100, // 单位：分
                      'openid'     => $user_weichat_info['openid'],
                      'attach' => "ordertype={$order_type}"
                  ];
    } else if ( $order_type == 'applyagent' ) {
      $real_res = Db::name( 'agent_apply' )->find( $orderId );
      \Think\Log::write( '代理申请信息' . json_encode( $real_res ) );
      $type = [1 => '代理商', 2 => '经销商'];
      $attributes = [ 'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
                      'body'       => '[' . Config::get( 'SMS_SIGN' ) . ']-'. $type[$real_res['agent_type']]. $real_res[ 'ticket' ],
                      'detail'     => '[' . Config::get( 'SMS_SIGN' ) . ']-'. $type[$real_res['agent_type']]. $real_res[ 'ticket' ] . '支付',
                      'out_trade_no' => $real_res[ 'ticket' ],
                      'total_fee' => $real_res['money'] * 100, // 单位：分
                      'openid'     => $user_weichat_info['openid'],
                      'attach' => "ordertype={$order_type}"
      ];
    }else {
      $this->error( '没有这样的订单' );
    }

    // dump($attributes);exit;
    \Think\Log::write( '微信下单信息' . json_encode( $attributes ), 'WARN' );
    $order    = new EasyOrder( $attributes );
    $result   = $this->easy_payment->prepare( $order );
    $js       = $this->easy_app->js;
    $prepayId = null;
    if ( $result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS' ) {
      $prepayId = $result->prepay_id;
    } else {
      $url = $backurl . '/res/fail';
      \Think\Log::write( '微信js支付失败' . json_encode( $result ), 'WARN' );
      \Think\Log::write( '微信js支付失败' . $url, 'WARN' );
      $this->error( "支付失败,联系管理员", $url );
    }
    $config = $this->easy_payment->configForJSSDKPayment( $prepayId );
    $this->assign( 'wx_js', $js->config( [ 'chooseWXPay' ] ) );
    $this->assign( 'wx_config', $config );
    $this->assign( 'backurl', $backurl  );
    $this->view->engine->layout( false );

    return $this->fetch();
  }

  /**
   * 支付坏回调类
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-17T09:52:09+080
   */
  public function pay_callback () {
    // \Think\Log::write('微信返回开始','WARN');
    $response = $this->easy_app->payment->handleNotify( function ( $notify, $successful ) {
      \Think\Log::write( '微信返回' . $notify, 'WARN' );
      \Think\Log::write( '支付结果' . $successful, 'WARN' );

      $ordertype = explode('=', $notify->attach)[1];

      L('order_type'. $ordertype);

      L('cache'.Cache::has($notify->out_trade_no));

      if(Cache::has($notify->out_trade_no)){
        return true;
      }
      Cache::set($notify->out_trade_no, $notify->out_trade_no, 7200);

      switch (strtolower($ordertype)){
        case 'goods':
          $orderMdl = new OrderModel();
          $order_res = $orderMdl->where('order_sn', $notify->out_trade_no)->find();
          if($successful){
            $orderMdl->order_handle($order_res, $notify->out_trade_no, NULL, '微信支付');
            /*$orderMdl->orderPay($order_res, 'Weichat', function ($order_res) {
              L('支付中....');
            }, function ($result) {
              if($result['code'] == 'success') echo 'success';
              else echo 'fail';
            });*/
          }else{
            $order_data[ 'order_status' ] = 1;
            $order_data[ 'pay_status' ] = 3;
            $order_data[ 'user_note' ]  = '用户支付失败';
            $order_data['pay_name'] = '微信支付';
            $orderMdl->where('order_id', $order_res->order_id)->update($order_data);
          }
          $msg = 'success';
          break;
        case 'recharge':
          //$recharge_id = explode('Recharge', $notify->out_trade_no)[1];
          $rechage_res = Db::name('user_recharge')->where('ticket', $notify->out_trade_no)->find();

          if($rechage_res['status'] == 2) {
            return true;
          }
          $result = Db::name('user_recharge')->where('id', $rechage_res['id'])->update([
            'status'=> 2,
            'paytime' => time(),
            'note' => '支付完成',
            'payway' => '微信支付',
            'weichat_sn' => $notify->transaction_id
          ]);
          L('微信充值完成'.$result);
          accountLog($rechage_res['user_id'], $rechage_res['money'], 1, '在线充值到账');
          $msg = 'success';
          break;
        case 'applyagent':
          // apply res

          $apply_res = Db::name('agent_apply')->where('ticket', $notify->out_trade_no)->find();

          //
          if($apply_res['status'] == 1) {
            return true;
          }
          (new \app\common\model\User())->agentApplyHandler($apply_res['id'], 1, function ($result) {
            L('微信充值完成'.$result);

          });

          $msg = 'success';
          break;
      }

      return true;
    });

    return $response;
  }

  /**
   * 处理充值
   * @author: slide
   * @param $rechage_res
   *
   */
  public function recharge_data($rechage_res, $order_no){
    $update_data = [
      'status' => 2,
      'updatetime' => time()
    ];
    Db::name('user_recharge')->where('id', $rechage_res['id'])->update($update_data);
    accountLog($rechage_res['user_id'], $rechage_res['money'], 1, '在线充值到账');

  }

  /**
   * 发送模板消息
   *
   * @param    string $url  [详细地址]
   * @param    string $type [场景]
   * @param    [array]                  $data [传输的数据]
   *
   * @return   [int]                   [发送id or false]
   * @Author   :  slade
   * @DateTime :2017-04-19T15:26:51+080
   */
  public function sendTemplateMsg ( $url = '', $type = '', $data = [], $userId = '' ) {
    if ( !$type || $type == '' || !$data || empty( $data ) ) {
      return false;
    }
    //L('测试', 'info');
    $user_id = ( $userId && $userId != '' ) ? $userId : Session::get( 'qt_userId' );
    $user_wx = Db::name('user')->where( "id", $user_id )->value( 'bindwx' );
    L('测试2_'.$user_id.'_'.$user_wx, 'info');
    if (  !$user_wx || $user_wx == '' || is_null( $user_wx )  ) {
      return false;
    }
    //L('测试2', 'info');
    $userOpenId = Db::name( 'userWeichatInfo' )->where( "id", $user_wx )->value( "openid" );
    if ( !$userOpenId || is_null( $userOpenId ) || $userOpenId == '' ) {
      return false;
    }
    $userService = $this->easy_app->user;
    $weichat_userInfo = $userService->get($userOpenId);
    if($weichat_userInfo['subscribe'] == 1) {
      L( 'OpendId' . $userOpenId, 'info' );
      $order_data  = weichat_template_data( $type, $data );
      $notice      = $this->easy_app->notice;
      $template_id = Config::get( 'WECHAT_TEAMPLATE' )[ $type ];
      $messageId   = $notice->to( $userOpenId )->url( $url )->withTemplateId( $template_id )->withData( $order_data )->send();
      L('模板消息content'.var_export($order_data, true));
      L('模板消息发送结果返回'.$messageId);
      if ( $messageId->errcode == 0 && $messageId->errmsg == 'ok' ) {
        return $messageId->msgid;
      } else {
        return false;
      }
    }
  }

  /**
   * 创建微信菜单
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-29T16:07:25+080
   */
  public function createWechatMenu () {
    $menu_res = Db::name( 'weichat_menu' )->select();

    $menu_data = setWxMenu( $menu_res );
    $menu      = $this->easy_app->menu;
    $result    = $menu->add( $menu_data );
    // dump($menu_res);
    // dump();
    if ( $result->errcode === 0 ) {
      $this->success( '生成菜单成功，3~5分钟生效，请稍后查看' );
    } else {
      $this->error( '生成失败' . $result->errmsg );
    }
  }

  /**
   * 接受微信的消息
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-29T17:20:21+080
   */
  public function getMsgFromWechat () {
    // 从项目实例中得到服务端应用实例。
    $server = $this->easy_app->server;
    $server->setMessageHandler( function ( $message ) {
      switch ( $message->MsgType ) {
        case 'event':
          $msg = $this->sendEventMsg( $message );
          break;
        case 'text':
          $msg = $this->sendTextMsg( $message->Content );
          break;
        case 'news':
          $msg = $this->sendNewsMsg( $message->Content );
          break;
        case 'image':
          return '收到图片消息';
          break;
        case 'voice':
          return '收到语音消息';
          break;
        case 'video':
          return '收到视频消息';
          break;
        case 'location':
          return '收到坐标消息';
          break;
        case 'link':
          return '收到链接消息';
          break;
        // ... 其它消息
        default:
          return '收到其它消息';
          break;
      }
      \Think\Log::write( '消息' . json_encode( $msg ), 'info' );

      return $msg ? $msg : '没有为您找到内容呢';
    } );

    $response = $server->serve();
    $response->send(); // Laravel 里请使用：return $response;
  }

  /**
   * 微信事件消息
   * @param    [type]                   $event [description]
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-05T15:04:16+080
   */
  public function sendEventMsg ( $message ) {
    Log::write(var_export($message->Event == 'SCAN', true));
    Log::write(var_export(isset($message->Ticket), true));
    Log::write(var_export($message->Event == 'SCAN' && isset($message->Ticket), true));
    Log::write(var_export($message, true));
    $msg = '没有您要的服务';
    if ( $message->Event == 'subscribe' ) {
      if(isset($message->Ticket)){
        $msg = new News([
          'title' => '欢迎注册世星电子科技商城!',
          'description' => '注册成为世星电子科技会员，享受超低优惠！',
          'url' => explode('_',$message->EventKey)[1],
          'image' => WEB_DOMAIN . '/public/static/images/wx_report_new.jpg'
        ]);
      }else{
        $msg = Db::name( 'weichat_info' )->where( 'id', 1 )->value( 'subscribe' );
        $msg = ( $msg && $msg != '' ) ? $msg : '欢迎关注我们，祝您生活愉快！';
      }

    } else if ( strtolower( $message->Event ) == 'click' ) {
      $key = $message->EventKey;
      $msg = $this->sendTextMsg( $key );
    } else if($message->Event == 'SCAN' && isset($message->Ticket)){
      // 公众号扫二维码
      // $msg = '<a href="'.$message->EventKey.'">点击我立即进行注册！</a>';
      $msg = new News([
        'title' => '欢迎注册世星电子科技商城!',
        'description' => '注册成为世星电子科技会员，享受超低优惠！',
        'url' => $message->EventKey,
        'image' => WEB_DOMAIN . '/public/static/images/wx_report_new.jpg'
      ]);
    }
    return $msg;
  }

  /**
   * 微信文本消息回复
   *
   * @param    [type]                   $content [description]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-05T15:04:52+080
   */
  public function sendTextMsg ( $content ) {
    $res = Db::name( 'weichat_keywords' )->where( "keyword", $content )->find();
    $msg = '';
	if($res){

		if ( $res[ 'type' ] == 'TEXT' ) {
		  $msg = Db::name( 'weichat_text' )->where( 'keyword', $content )->value( 'text' );
		} else if ( $res[ 'type' ] == 'NEWS' ) {
		  $msg = $this->sendNewsMsg( $content );
		}
	}else{
		$msg = '找不到您需要的服务';
	}

	\Think\Log::write('MESSAGE:'.json_encode($res), 'error');
	\Think\Log::write('MESSAGE:'.$content, 'error');
    return $msg ? $msg : '找不到你要的内容！';
  }

  /**
   * 图文回复
   *
   * @param    [type]                   $content [description]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-05-05T15:29:03+080
   */
  public function sendNewsMsg ( $content ) {
    \Think\Log::write( $content );
    $msg_res = Db::name( 'weichat_img' )->where( 'keyword', $content )->select();
    $msg     = [];
    foreach ( $msg_res as $k => $v ) {
      $news  = new News( [ 'title' => $v[ 'title' ], 'description' => $v[ 'desc' ], 'url' => $v[ 'url' ], 'image' => WEB_DOMAIN . str_replace( '\\', '/', $v[ 'pic' ] ), ] );
      $msg[] = $news;
    }

    return $msg;
  }

  /**
   * 创建二维码
   * @author: slide
   * @param $url
   * @return mixed
   *
   */
  public function create_wx_qrcode($url){
    $qrcode = $this->easy_app->qrcode;
    //dump($qrcode);
    $result = $qrcode->forever($url);// 或者 $qrcode->forever("foo");
    $ticket = $result->ticket; // 或者 $result['ticket']
   //  $url = $result->url;
    $url = $qrcode->url($ticket);

    return json(['status'=>2000, 'data'=>$url]);
  }
}
