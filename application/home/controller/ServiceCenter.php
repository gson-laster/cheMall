<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 上午 11:50
 */

namespace app\home\controller;
use app\common\controller\Coupon;
use app\common\controller\TyApi;
use think\Config;
use think\Db;
use think\Session;

/**
 * 服务中心控制器
 * Class ServiceCenter
 *
 * @package app\home\controller
 */
class ServiceCenter extends Validate {
    protected $userId = '';

    protected function _initialize(){
      parent::_initialize();
      if(Session::get('userInfo')['agent_type'] != 1) {
       $this->error('您还没有服务中心权限，请先申请！', url('home/user/index'), [], 0);
      }
      $this->userId = Session::get('qt_userId');
    }

  /**
   * 服务中心
   * @methods
   * @desc
   * @author slide
   *
   */
  public function index () {
    // 此处可优化
    $serviceCoin = model('service_center_coin')->where('user_id', $this->userId)->find();
    cache_data('service_coupon_comp_ticket', $serviceCoin['comp_ticket']);
    cache_data('service_coupon_recharge_quota', $serviceCoin['recharge_quota']);
    cache_data('service_coupon_distribution_ticket', $serviceCoin['distribution_ticket']);
    return $this->fetch('index', ['serviceCoin' => $serviceCoin, 'userInfo' => Session::get('userInfo')]);
  }

  /**
   * 申请充值额度
   * @methods
   * @desc
   * @author slide
   *
   */
  public function applyRechage(){
    if($this->request->isAjax()){
      $data = $this->request->post();

      if(!isset($data['money']) || $data['money'] == '' || $data['money'] == 0) return $this->ajax(4000, '请先确认金额');

      $notProcessedRes = Db::name('apply_recharge')->where(['user_id'=> $this->userId, 'status' => 1])->find(); // 未处理记录

      if($notProcessedRes) {
        return $this->ajax(4000, '您还有申请未处理，请耐心等候客户审核！');
      }
      // add raws
      $data['user_id'] = $this->userId;
      $data['ticket'] = careateTicket('AC');
      $data['createtime'] = time();
      $data['updatetime'] = time();
      $data['status'] = 1;
      $result = Db::name('apply_recharge')->insert($data);
      if($result) {
        return $this->ajax(2000, '申请成功,请耐心等候客户审核!');
      } else {
        return $this->ajax(4000, '申请失败，请重新申请！');
      }
    } else{
      //$total = Db::name('apply_recharge')->where(['user_id'=> $this->userId, 'status' => 2])->count('money');
      return $this->fetch('service_to_rechage');
    }
  }

  /**
   * 商家充值列表
   * @methods
   * @desc
   * @author slide
   * @param int    $type 收入或者支付 1收入2支出
   * @param string $start
   * @param string $end
   * @param int    $page
   *
   * @return mixed
   *
   */
  public function applyrechargelist($type='', $start = '', $end = '', $page = 1){
    $where = [];

    if($type) $where['type'] = $type;

    if($start && !$end) {
      $where['createtime'] = ['between', [strtotime($start), time()]];
    }elseif (!$start && $end){
      $where['createtime'] = ['<', strtotime($end)];
    }else{
      $where['createtime'] = ['between', [strtotime($start), strtotime($end)]];
    }

    $result = Db::name('apply_recharge')->where(['user_id' => $this->userId])->order('createtime desc')->paginate(12, ['page' => $page, 'query' => ['start' => $start, 'end' => $end]]);

    $month = to_sex_month(true);
    $data = [];

    foreach ( $month as $key ) {
      $temp_data = [];
      $total = 0;
      foreach ($result as $k => $v){
        if($v['createtime'] >= intval($key[0]) && $v['createtime'] < intval($key[1])){
          $temp_data[] = $v;
          $total += $v['money'];
        }

      }
      if(count($temp_data) > 0) {
        $temp = [
          'date' => date('Y年m月', $key[0]),
          'data' => $temp_data,
          'total'=> $total
        ];
        $data[] = $temp;
      }
    }

    if($this->request->isAjax()){
      return $this->ajax(2000, '查询成功', '', $data);
    }else{
      return $this->fetch('apply_recharge_list', ['list' => $data]);
    }
  }

  /**
   * 商家充值详情
   * @methods
   * @desc
   * @author slide
   *
   * @param int $id
   *
   * @return mixed|void
   *
   */
  public function applyRechargeDetail($id = 0) {
    if(!$id) return $this->error('缺少必要参数');
    $result = Db::name('apply_recharge')->find($id);
    if($result){
      return $this->fetch('user_recharge_detail', ['result' => $result]);
    }else{
      return $this->error('没有这样的信息');
    }
  }

  /**
   * 服务中心为会员充值/处理充值(此页面可通过会员中心扫码进来)
   * @methods
   * @desc
   * @author slide
   *
   */
  public function userRechage(){
    if($this->request->isPost()){
      $data = $this->request->post();
      if(!isset($data['to_phone']) || $data['to_phone'] == ''||!isset($data['to_number']) || $data['to_number'] == '' || $data['to_number'] == 0) return $this->ajax(4000, '信息填写错误');
      // 充值动作
      $to_user = model('user')->where('phone', $data['to_phone'])->find();

      if(!$to_user) return $this->ajax(4000, '用户不存在');

      // 存记录
      Db::startTrans();
      try{
        $userRechargeMdl = model('user_recharge');
        $userRechargeMdl->data([
          'service_id' => $this->userId,
          'user_id' => $to_user->id,
          'money'   => $data['to_number'],
          'payway'  => '服务中心当面支付',
          'ticket'  => careateTicket('UA'),
          'status'  => 2,
          'note'    => '充值订货额',
          'paytime' => time()
        ], true);
        $userRechargeMdl->allowField(true)->isUpdate(false)->save();

        // 扣除服务中心配货额
        // 减去服务费用0.01
        $serviceFree = $data['to_number'] * Config::get('service_center_user_recharge_free');

        // 计入收入记录表
        $to_user_phone = substr($to_user->phone, 0, 3).'****'.substr($to_user->phone, -4);
        Coupon::saveServiceFreeNote($this->userId, $to_user->id, 1, $serviceFree, Config::get('service_center_user_recharge_free'), (Config::get('service_center_user_recharge_free')* 100).'%充值服务收入,充值号码【'.$to_user_phone.'】');

        $realMoney = $data['to_number'] - $serviceFree;
        Coupon::updateServiceCoin($this->userId, 1, $realMoney, 2, '用户充值划减');
        // 增加用户的余额
        accountLog($to_user->id, $data['to_number'], 1, '服务中心充值到账');
        Db::commit();
        return $this->ajax(2000, '充值成功');
      }catch (\Exception $e){
        L('服务中心'.$this->userId.'为用户'.$to_user->id.'充值失败'.$e);
        Db::rollback();
        return $this->ajax(4000, '充值失败');
      }
    }else{
      $canUseNumber = model('service_center_coin')->where('user_id', $this->userId)->value('recharge_quota');
      $to_phone = $this->request->param('from_id') ? model('user')->where('id', $this->request->param('from_id'))->value('phone') : '';

      return $this->fetch('user_recharge', ['canUseNumber' => is_null($canUseNumber) ? 0 : $canUseNumber, 'to_phone' => $to_phone]);
    }
  }

  /**
   * 会员充值列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function userRechargeList($page=1){

    $where=[];
    if(input('user_id')){
      $where['a.user_id']=input('user_id');
    }

    $result_user_rech= Db::name('user_recharge')->alias('a')->field(['a.*','b.phone','b.id as bid'])->join('__USER__ b','a.user_id=b.id','left')->where($where)->order('a.id desc')->paginate(Config::get('pageSize'), false, [
      'page' => $page
    ]);

    if($result_user_rech){
      if($this->request->isAjax()){
        return  $json=[
          'code'=>200,
          'data'=>$result_user_rech

        ];
      }else{
        $this->assign("data",$result_user_rech);
        return  $this->fetch();

      }
    }else{

      if ($this->request->isAjax()) {
        return $json = [
          'code' => 400,
          'data' => $result_user_rech
        ];
      } else {
        $this->assign("data", $result_user_rech);
        return $this->fetch();
      }
    }

  }

  /**
   * 会员充值明细
   * @methods
   * @desc
   * @author slide
   *
   */
  public function userRechargeDetail($id=''){

    $result_user_rech= Db::name('user_recharge')->alias('a')->field(['a.*','b.id as bid','b.phone','b.nickname'])->join('__USER__ b','a.user_id=b.id','left')->find($id);
    if($result_user_rech){
      if($this->request->isAjax()){
        return  $json=[
          'code'=>200,
          'data'=>$result_user_rech

        ];
      }else{
        $this->assign("data",$result_user_rech);
        return  $this->fetch();

      }
    }else{

      if ($this->request->isAjax()) {
        return $json = [
          'code' => 400,
          'data' => $result_user_rech
        ];
      } else {
        $this->assign("data", $result_user_rech);
        return $this->fetch();
      }
    }

  }

  /**
   * 配货券列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function goodsCouponsList($page = 1){
    $result = model('service_center_coin_note')->where(['user_id' => $this->userId, 'coin_type' => 2])->order('id desc')->paginate(12, ['page' => $page]);

    $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('distribution_ticket');

    return $this->fetch('goods_coupon_list', ['list' => $result, 'userCouponNumber' => is_null($userCouponNumber) ? 0 : $userCouponNumber]);
  }

  /**
   * 提交并且处理配货券（配货券换货）
   * @methods
   * @desc
   * @author slide
   *
   */
  public function goodsCoupon(){
    if($this->request->isAjax()){
      $data = $this->request->post();

      if(!isset($data['number']) || $data['number'] == '' || $data['number'] == 0) return $this->ajax(4000, '请先确认兑换数量！');

      if(!isset($data['address']) || $data['address'] == '') return $this->ajax(4000, '请先填写地址');
      if(!isset($data['consignee']) || $data['consignee'] == '') return $this->ajax(4000, '请先确认个人信息');
      if(!isset($data['phone']) || $data['phone'] == '') return $this->ajax(4000, '请先确认个人信息');

      $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('distribution_ticket');

      if(!$userCouponNumber || $userCouponNumber < $data['number']) return $this->ajax(4000, '数量不够，不能申请！');

      Db::startTrans();
      try{
        // 减少会员的券
        Coupon::updateServiceCoin($this->userId, 2, $data['number'],2, '配货券换货');
        // 生成记录
        Coupon::saveServiceCouponToGoods($this->userId, $data['number'], $data['address'], $data['consignee'], $data['phone'], 1, 1);
        Db::commit();
        return $this->ajax(2000, '申请成功,请耐心等候！');
      }catch (\Exception $e){
        L('配货券换货错误'.$e);
        Db::rollback();
        return $this->ajax(4000, '申请失败，请重试');
      }
    }else{
      // 默认地址
      $address = null;
      $user = model('user')->find(Session::get('qt_userId'));
      if(isset($user['address_now'])){
        $address = model('address')->find($user['address_now']);
      }
      $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('distribution_ticket');
      return $this->fetch('goods_coupon', ['address' => $address, 'userCouponNumber' => $userCouponNumber]);
    }
  }

  /**
   * 赠品券列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function giftCouponList($page = 1){
    $result = model('service_center_coin_note')->where(['user_id' => $this->userId, 'coin_type' => 3])->order('id desc')->paginate(12, ['page' => $page]);

    $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('comp_ticket');

    return $this->fetch('gift_coupon_list', ['list' => $result, 'userCouponNumber' => $userCouponNumber]);
  }

  /**
   * 提交并且处理赠品券（赠品券换货）
   * @methods
   * @desc
   * @author slide
   *
   */
  public function giftCoupon(){
    if($this->request->isAjax()){
      $data = $this->request->post();

      if(!isset($data['number']) || $data['number'] == '' || $data['number'] == 0) return $this->ajax(4000, '请先确认兑换数量！');

      if(!isset($data['address']) || $data['address'] == '') return $this->ajax(4000, '请先填写地址');
      if(!isset($data['consignee']) || $data['consignee'] == '') return $this->ajax(4000, '请先确认个人信息');
      if(!isset($data['phone']) || $data['phone'] == '') return $this->ajax(4000, '请先确认个人信息');

      $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('comp_ticket');

      if(!$userCouponNumber || $userCouponNumber < $data['number']) return $this->ajax(4000, '数量不够，不能申请！');

      Db::startTrans();
      try{
        // 减少会员的券
        Coupon::updateServiceCoin($this->userId, 3, $data['number'],2, '赠品券换货');
        // 生成记录
        Coupon::saveServiceCouponToGoods($this->userId, $data['number'], $data['address'], $data['consignee'], $data['phone'], 2, 1);
        Db::commit();
        return $this->ajax(2000, '申请成功,请耐心等候！');
      }catch (\Exception $e){
        L('配货券换货错误'.$e);
        Db::rollback();
        return $this->ajax(4000, '申请失败，请重试');
      }
    }else{
      // 默认地址
      $address = null;
      $user = model('user')->find(Session::get('qt_userId'));
      if(isset($user['address_now'])){
        $address = model('address')->find($user['address_now']);
      }
      $userCouponNumber = model('service_center_coin')->where('user_id', Session::get('qt_userId'))->value('comp_ticket');

      return $this->fetch('gift_coupon', ['address' => $address, 'userCouponNumber' => $userCouponNumber]);
    }
  }

  /**
   * 服务收入明细
   * @methods
   * @desc
   * @author slide
   *
   */
  public function serviceSalesDetail($start = '', $end = ''){
    $where['service_id'] = $this->userId;
    if($start != '' && $end == '') $where['createtime'] = ['>=', strtotime($start)];
    elseif($start == '' && $end != '') $where['createtime'] = ['<=', strtotime($end)];
    elseif($start != '' && $end != '') $where['createtime'] = ['between', [strtotime($start), strtotime($end)]];
    $list = model('service_center_free')->where($where)->select();
    $date = to_sex_month(true);
    $result = [];
    $totalNumber = 0;
    foreach ($date as $k => $v) {
      $temp_data = [];
      $total = 0;
      foreach ($list as $key => $value) {
        if(strtotime($value['createtime']) >= $v[0]*1 && strtotime($value['createtime']) < $v[1]*1) {
          $temp_data[] =$value;
          $total += $value['number'];
          $totalNumber+=$value['number'];
        }
      }
      if(count($temp_data) > 0) {
        $temp = [
          'date' => date('Y年m月', $v[0]),
          'data' => $temp_data,
          'total' => $total
        ];
        $result[] = $temp;
      }
    }

    return $this -> fetch('', ['list' => $result, 'total' => $totalNumber]);
  }

  /**
   * 积分换货
   * @methods
   * @desc
   * @author slide
   *
   */
  public function recodeToGoods(){

    $type = $this->request->get('type') ? $this->request->get('type') : 1;

    $url = $type == 1 ? 'home/integral/inchange' : 'home/Integral/to_exchange_trading_stamp';

    $background_color = 'ffffff'; // 背景颜色 可选 默认白色
    $color = "00000"; // 二维码颜色
    $margin = 1; // 二维码间隔 默认1
    $size = 20; // 大小
    $content = WEB_DOMAIN.url($url.'?service_id='.$this->userId); // 携带参数
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

    return $this->request->isAjax() ? $this->ajax(2000, '成功', '', $urlArr['url'].'?'.$urlArr['query']): $this->fetch('qrcode_for_user_get_goods', ['url' => $urlArr['url'].'?'.$urlArr['query']]);
  }


    /***
     * 服务中心跳转
     * @param int $page
     * @return mixed
     * hot_service  //常用
     * recommond_service //推荐
     */
  public  function  payserviceCenter($page=1){
      $serviceid=Db::name('user_recharge')->field(['service_id'])->where('user_id',Session::get('qt_userId'))->select();
      $hot_service=Db::name('service_center')->where('id','in',$serviceid)->select();
      $recommond_servie=Db::name('service_center')->where('recommend',1)->paginate(Config::get('pageSize'), false, [
          'page' => $page
      ]);
      $this->assign("hot_service",$hot_service);
      $this->assign("recommond_servie",$recommond_servie);
      return  $this->fetch();
  }

  /**
   * 账本详细
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public function money_content($start = '', $end = ''){
    $where['user_id'] = $this->userId;
    $where['coin_type'] = 2;
    $where['type'] = 1;
    if($start != '' && $end == '') $where['createtime'] = ['>=', strtotime($start)];
    elseif($start == '' && $end != '') $where['createtime'] = ['<=', strtotime($end)];
    elseif($start != '' && $end != '') $where['createtime'] = ['between', [strtotime($start), strtotime($end)]];

    $list = model('service_center_coin_note')->where($where)->select();

    $date = to_sex_month(true);
    $result = [];
    $totalNumber = 0;
    foreach ($date as $k => $v) {
      $temp_data = [];
      $total = 0;
      foreach ($list as $key => $value) {
        if(strtotime($value['createtime']) >= $v[0]*1 && strtotime($value['createtime']) < $v[1]*1) {
          $temp_data[] = $value;
          $totalNumber += $value[ 'number' ];
          $total       += $value[ 'number' ];
        }
      }
      if(count($temp_data) > 0) {
        $temp = [
          'date' => date('Y年m月', $v[0]),
          'data' => $temp_data,
          'total' => $total
        ];
        $result[] = $temp;
      }
    }

    return $this -> fetch('', ['list' => $result, 'total' => $totalNumber]);
  }

}
