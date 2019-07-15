<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24 0024
 * Time: 上午 10:31
 */

namespace app\common\controller;

use app\common\model\ServiceCenterCoin;
use app\common\model\ServiceCenterCoinNote;
use app\common\model\ServiceCenterCouponToGoods;
use app\common\model\ServiceCenterFree;
use think\Db;
use Think\Exception;

/**
 * 服务中心货币处理便捷封装
 * Class Coupon
 *
 * @package app\common\controller
 */
class Coupon {
  protected static $serviceCoinMdl = null;
  protected static $serviceCoinNoteMdl = null;
  protected static $serviceCoponToGoodsMdl = null;
  protected static $serviceFreeMdl = null;

  /**
   * 获取服务中心货币model
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public static function getServiceCoinMdl () {
    return is_null(self::$serviceCoinMdl) ? new ServiceCenterCoin() : self::$serviceCoinMdl;
  }

  /**
   * 获取服务中心货币记录model
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public static function getServiceCoinNoteMdl () {
    return is_null(self::$serviceCoinNoteMdl) ? new ServiceCenterCoinNote() : self::$serviceCoinNoteMdl;
  }

  /**
   * 获取服务中心积分或者券转商品model
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public static function getServiceCouponToGoodsMdl () {

    return is_null(self::$serviceCoponToGoodsMdl) ? new ServiceCenterCouponToGoods() : self::$serviceCoponToGoodsMdl;
  }

  /**
   * 获取服务收入模型
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public static function getServiceFreedl () {

    return is_null(self::$serviceFreeMdl) ? new ServiceCenterFree() : self::$serviceFreeMdl;
  }

  /**
   * 更新服务中心货币数和记录
   * @methods
   * @desc
   * @author slide
   *
   * @param        $user_id     用户id
   * @param        $coin_type   币类型 1、充值额度；2、配货券；3、赠品券
   * @param int    $number      数量
   * @param int    $type        1、进账；2出账
   * @param string $note        操作说明
   *
   */
  public static function updateServiceCoin($user_id, $coin_type, $number = 0, $type = 1, $note = ''){
    if(!$user_id || !$coin_type) return false;

    $field = '';
    switch ($coin_type){
      case 1:
        $field = 'recharge_quota';
        break;
      case 2:
        $field = 'distribution_ticket';
        break;
      case 3:
        $field = 'comp_ticket';
        break;
    }

    // 增减
    $text = '';
    $num = $number;
    if($type == 1) {
      $number = $number;
      $text = '增加';
    }else{
      $number = $number * (-1);
      $text = '减少';
    }
    Db::startTrans();
    try{
      // 更新货币表
      $serviceCoinMdl =  self::getServiceCoinMdl();
      $where['user_id'] = $user_id;
      $result = $serviceCoinMdl::where($where)->find();

      $order_id = 0;
      $order_sanpshot = '';
      if($result) {
        // update
        $res = $serviceCoinMdl::where($where)->setInc($field, $number);
        $order_id = $result['id'];
        $order_sanpshot = json_encode($result, true);
      }else{
        // create
        $data = [];
        $data[$field] = $num;
        $data['user_id'] = $user_id;
        $serviceCoinMdl->data($data, true);
        $res = $serviceCoinMdl->allowField(true)->isUpdate(false)->save();
        L('___________________________________________'.$serviceCoinMdl->id);
        $order_id = $serviceCoinMdl->id;
        $order_sanpshot = json_encode($res, true);
      }

      // 更新货币记录表
      self::saveServiceCoinNote($user_id, $type, $coin_type, $num, $order_id, $order_sanpshot, $note);

      Db::commit();
      return true;
    }catch (\Exception $e){
      L('服务中心货币更改失败'.$e);
      Db::rollback();
      return false;
    }
  }

  /**
   * 生成服务中心货币变动记录
   * @methods
   * @desc
   * @author slide
   *
   * @param        $user_id         用户id
   * @param int    $coin_type       币类别1、充值额度；2、配货券；3、赠品券
   * @param int    $type            操作记录 1、进账；2出账
   * @param int    $order_id        相关表的id
   * @param string $order_snapshot  相关表的快照
   * @param string $note            变动说明
   *
   * @return mixed
   *
   */
  public static function saveServiceCoinNote($user_id, $type = 1, $coin_type = 1, $number = 0, $order_id = 0, $order_snapshot = '', $note = ''){
    $serviceCoinNoteMdl = self::getServiceCoinNoteMdl();

    $data = [
      'user_id' => $user_id,
      'coin_type' => $coin_type,
      'type' => $type,
      'number' => $number,
      'note' => $note,
      'order_id' => $order_id,
      'order_snapshot' => $order_snapshot
    ];

    $serviceCoinNoteMdl->data($data, true);

    return $serviceCoinNoteMdl->save();
  }

  /**
   * 生成券转商品的申请
   * @methods
   * @desc
   * @author slide
   *
   * @param        $user_id           服务中心id
   * @param int    $coupon_num        券的数量
   * @param string $address           申请的地址
   * @param string $consignee         申请人
   * @param string $phone             申请人的电话
   * @param int    $coupon_ty         券的类型1、兑货券；2、赠品券
   * @param int    $status            当前的类型
   *
   * @return mixed
   *
   */
  public static function saveServiceCouponToGoods($user_id, $coupon_num = 0, $address = '', $consignee = '', $phone = '', $coupon_ty = 1, $status = 1){
    $data = [
      'user_id' => $user_id,
      'coupon_num' => $coupon_num,
      'address' => $address,
      'consignee' => $consignee,
      'phone' => $phone,
      'coupon_ty' => $coupon_ty,
      'status' => $status
    ];
    $serviceCouponToGoodsMdl = self::getServiceCouponToGoodsMdl();
    $serviceCouponToGoodsMdl->data($data, true);
    return $serviceCouponToGoodsMdl->save();
  }

  /**
   * 服务中心收入记录
   * @methods
   * @desc
   * @author slide
   *
   * @param int    $service_id
   * @param int    $user_id
   * @param int    $type    1、充值额度；2、配货券；3、礼品券
   * @param int    $number
   * @param int    $commission  变动比例（1%或者1.5%）
   * @param string $note
   *
   * @return mixed
   * @throws Exception
   *
   */
  public static function saveServiceFreeNote($service_id = 0, $user_id = 0, $type = 1, $number = 0,  $commission = 0, $note = ''){
    if(!$service_id || !$user_id) throw new Exception('fail to change service free: service_id or user_id exit');

    $data = [
      'user_id' => $user_id,
      'service_id' => $service_id,
      'type' => $type,
      'number' => $number,
      'commission' => $commission,
      'note' => $note
    ];
    $serviceFreeMdl = self::getServiceFreedl();

    $serviceFreeMdl->data($data, true);

    return $serviceFreeMdl->allowField(true)->isUpdate(false)->save();

  }
}
