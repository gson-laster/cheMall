<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 2:51
 */

namespace app\home\controller;
use app\common\controller\Coupon;
use think\Config;
use app\common\model\Integral as integralModel;
use think\Exception;
use think\Session;
use think\Db;
class Integral extends Validate
{
    protected $integralMdl;
    protected function _initialize()
    {
        parent::_initialize();
        $this->integralMdl = new integralModel();
    }


    protected function createOrderSn () {
        $order_id = '';
        while ( true ) {
            // 订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
            $order_id_main = date( 'YmdHis' ) . rand( 10000000, 99999999 );
            // 订单号码主体长度
            $order_id_len = strlen( $order_id_main );
            $order_id_sum = 0;
            for ( $i = 0; $i < $order_id_len; $i++ ) {
                $order_id_sum += (int) ( substr( $order_id_main, $i, 1 ) );
            }
            // 唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
            $order_id = "E" . $order_id_main . str_pad( ( 100 - $order_id_sum % 100 ) % 100, 2, '0', STR_PAD_LEFT );

            return $order_id;
        }
    }


    /**
     * 开始订货
     *@param
     *$order_amount
     *$user_id
     */
    public function orderGoodes()
    {
        $data = $this->request->post();
        $validate = new \app\common\validate\Integral();
        $data_res =$validate->scene('add')->check($data);
        if ($data_res != true) {
            if ($this->request->isAjax()) {
                $json = [
                    'code' => 400,
                    'data' => $validate->getError()
                ];
                return $json;
            } else {
                $this->error($data_res);
            }
        } else {
            $res_user = Db::name('user')->where('id', $data['user_id'])->find();
            if ($res_user['user_vb'] < $data['order_amount']) {
                if ($this->request->isAjax()) {
                    return $json = [
                        'code' => 400,
                        'data' => "余额不足"
                    ];
                } else {
                    $this->error("余额不足");
                }
            } else {

                // 启动事务
                Db::startTrans();
                try{
                $data['integral'] = $data['order_amount'] *Config::get('integralratio');
                $data['endtime'] = strtotime("+1 year");
                $this->integralMdl->save($data);
                accountLog($data['user_id']*1, intval($data['order_amount']), 2, '订货');
                accountLog($res_user['pid'], $data['order_amount']*Config::get('proportions'), 1, '推荐收入（'.$res_user['nickname'].'）');
                    $infos=[
                        'user_id'=>$res_user['pid'],
                        'order_id'=>$this->createOrderSn (),
                        'commission'=>'10%',
                        'total_money'=>$data['order_amount']*Config::get('proportions') ,
                        'level'=>$res_user['agent_type'],
                        'type'=>2,
                        'user_info'=>['nickname'=>$res_user['nickname'],'phone'=>$res_user['phone'],'id'=>$res_user['id']]
                    ];
                    divideNote($infos);
                ex_balancechange($data['user_id'], "订货赠送", $data['order_amount'], '+', '2');
                ex_balancechange($data['user_id'], "订货增加", $data['order_amount']*Config::get('integralratio'), '+', '3');
                $res_integral = "select sum(order_amount)  from ty_integral  where user_id=" . $res_user['id'];
                $res_leve = [];
                if ($res_integral >=2999) {
                    $res_leve['agent_type'] = 4;
                }
                if ($res_integral >= 29999) {
                    $res_leve['agent_type'] = 3;
                }
                if ($res_integral >=299999) {
                    $res_leve['agent_type'] = 2;
                }
                if ($res_integral >=2999999) {
                    $res_leve['agent_type'] = 1;
                }
                Db::name('user')->where('id', $res_user['id'])->update($res_leve);
                Db::commit();
                return $this->ajax(2000, '订货成功');
                }catch(  Exception $e){
                    L("订货失败".$e);
                    Db::rollback();
                    return $this->ajax(4000, '订货失败');
                }
            }

        }

    }

    /***
     * 积分兑换跳转
     * @return mixed
     */
    public   function inchange(){
        $result= Db::name('user')->find($this->request->param('service_id'));
        $this->assign("phone",$result['phone']);
        return  $this->fetch('user/point_to_goods');
    }
    /**
     * 积分换货
     * @param
     * type 请求类型  1 跳转 2兑换
     * user_id 请求的用户ID
     * service_id  服务中心ID
     * integral    兑换积分数
     */
    public function ingegralChange()
    {
        $data = input();
        if (isset($data['type']) == "1") {
            if (Session::get('qt_userId')) {
                $res_user = Db::name('user')->where('id', Session::get('qt_userId'))->find();
                $this->assign("integral", $res_user['record']);
                return $this->fetch();
            } else {
                $this->error("请先登录", "home/login/login");
            }
        } else {
            if (Session::get('qt_userId')) {
                $res_user = Db::name('user')->where('id', Session::get('qt_userId'))->find();
                if ($res_user['record'] < $data['integral']) {
                    $this->error("积分余额不足！");
                }
                if (!isset($data['phone'])) {
                    $this->error("服务中心代码为空！");
                }
                if (!isset($data['integral'])) {
                    $this->error('输入的积分余额为空！');
                }
                $res_service = Db::name('user')->where('phone', $data['phone'])->find();
                if ($res_service != null) {
                    $integralup=$data['integral']*(Config::get('service_center_user_coupon_to_goods_free')+1);
                    $res_recodecg= ex_balancechange(Session::get('qt_userId'), "积分换货", $data['integral'], '-', 1);
                    $res_servicrecodecg= Coupon::updateServiceCoin($res_service['id'], 2,$integralup, 1, "会员换货[" . $res_user['nickname'] . "]");
                    $integralfree=$data['integral']*Config::get('service_center_user_coupon_to_goods_free');
                    $Percentage=Config::get('service_center_user_coupon_to_goods_free')*100;
                    Coupon::saveServiceFreeNote($res_service['id'], Session::get('qt_userId'),$integralfree, Config::get('service_center_user_coupon_to_goods_free'),''. $Percentage.'%积分换货服务费');
                    if ($res_recodecg && $res_servicrecodecg) {
                        if ($this->request->isAjax()) {
                            return $json = [
                                'code' => 200,
                                'data' => "换货成功！"
                            ];
                        } else {
                            $this->success("换货成功！");
                        }
                    } else {
                        if ($this->request->isAjax()) {
                            return $json = [
                                'code' => 400,
                                'data' => "换货失败！"
                            ];
                        } else {
                            $this->success("换货失败！");
                        }
                    }
                } else {
                    $this->error("服务中心号码不存在，请您联系咨询服务中心！");
                }
            } else {
                $this->error("请先登录", "home/login/login");
            }
        }
    }

    /**
     *积分兑换余额
     * type 请求类型  1 跳转 2，兑换
     * user_id 请求的用户ID
     * integral    兑换积
     */
    public function integralChangeVb()
    {
        $data = input();
        if (isset($data['type']) == 1) {
            if (Session::get('qt_userId')) {
                $res_user = Db::name('user')->where('id', Session::get('qt_userId'))->find();
                $this->assign("integral", $res_user['record']);
                return $this->fetch();
            } else {
                $this->error("请先登录", "home/login/login");
            }
        } else {
            if (Session::get('qt_userId')) {
                $res_user = Db::name('user')->where('id', Session::get('qt_userId'))->find();
                if ($res_user['record'] > $data['integral']) {
                    $vbs = $data['integral']*Config::get('integralratio_vb');
                    $res_vb = accountLog($data['user_id'], $vbs, 1, '积分兑换余额');
                    $res_integral = ex_balancechange($data['user_id'], "积分兑换余额", $data['integral'], '-', 1);
                    if ($res_vb && $res_integral) {
                        if ($this->request->isAjax()) {
                            return $json = [
                                'code' => 200,
                                'data' => "积分兑换余额成功！"
                            ];
                        } else {
                            $this->success("积分兑换余额成功！");
                        }
                    } else {
                        if ($this->request->isAjax()) {
                            return $json = [
                                'code' => 400,
                                'data' => "积分兑换余额失败！"
                            ];
                        } else {
                            $this->success("积分兑换余额失败！");
                        }
                    }
                } else {
                    if ($this->request->isAjax()) {
                        return $json = [
                            'code' => 400,
                            'data' => "积分余额不足！"
                        ];
                    } else {
                        $this->error("积分余额不足！");
                    }
                }
               } else {
                $this->error("请先登录", "home/login/login");
            }
        }
    }



    /***
     * 囎品券换货跳转
     * @return mixed
     */
      function  to_exchange_trading_stamp(){

          if(Session::get('qt_userId'))
          $result= Db::name('user')->find($this->request->param("service_id"));
          $this->assign("phone",$result['phone']);
          $trading_stamp=Db::name('user')->where('id',Session::get('qt_userId'))->value('trading_stamp');
          $this->assign('trading_stamp',$trading_stamp);
          return  $this->fetch('user/goods_coupon');
      }

    /** 赠品券换货
     * @param string $service_id
     * @param string $trading_stamp
     *
     *
     */


    function  exchangetra($page=1){
        $where['userid']=Session::get('qt_userId');
        $where['change_type']=2;
        $result_user_balance=  Db::name('balance_change')->alias('a')->field(['a.*','b.phone','b.id as bid'])->join('__USER__ b','a.userid=b.id')->where($where)->order('a.id desc')->paginate(Config::get('pageSize'), false, [
            'page' => $page
        ]);
        $user=Db::name('user')->where('id',Session::get('qt_userId'))->value('trading_stamp');
        $this->assign("data",$result_user_balance);
        $this->assign('trading_stamp',$user);
        return  $this->fetch('user/goods_coupon');
    }

    /**
     * 赠品券换货
     * @param string $phone
     * @param string $trading_stamp
     * @return mixed|\think\response\Json|void
     */
    function   exchangetrading_stamp($phone='',$trading_stamp=''){
          $uid=Session::get('qt_userId');
          $userinfos=Db::name('user')->where('id',$uid)->find();
          $service_info=Db::name('user')->where('phone',$phone)->find();
        if(!$service_info||$service_info['agent_type']!=1){
              return $this->ajax('4000','没有这样的服务中心','','');
        }
          if($this->request->isAjax()){
            if($userinfos['trading_stamp']>$trading_stamp){
                ex_balancechange(Session::get('qt_userId'), "赠品券换货", $trading_stamp, '-', 2);
                ex_balancechange($service_info['id'],'会员赠品券换货',$trading_stamp,'+',2);
                Coupon::updateServiceCoin($service_info['id'],3,$trading_stamp,1,'会员换货['.$userinfos['nickname'].']');
                return $this->ajax('2000','兑换成功！','','');
            }else{
                return $this->ajax('4000','余额不足','','');
            }
      }else{
              if($userinfos['trading_stamp']>$trading_stamp){
                  ex_balancechange(Session::get('qt_userId'), "赠品券换货", $trading_stamp, '-', 2);
                  Coupon::updateServiceCoin($service_info['id'],3,$trading_stamp,1,'会员换货['.$userinfos['nickname'].']');
                  return   $this->fetch('', ['trading_stamp' => $userinfos['trading_stamp']]);
              }else{
                  return $this->error("余额不足");
              }

          }

      }


}
