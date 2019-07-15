<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24 0024
 * Time: 下午 3:09
 */

namespace app\admin\controller;
use app\common\controller\Coupon;
use think\Config;
use think\Db;
use app\common\controller\Message;

/**
 * 后台处理服务中心券换货，充值配货额等
 * Class ServiceCenterCoin
 *
 * @package app\admin\controller
 */
class ServiceCenterCoin extends AdminBase {

  /**
   * 处理服务中心申请订货额
   * @methods
   * @desc
   * @author slide
   *
   * @param int    $page      页码
   * @param string $keyword   服务中心的名字或者电话
   *
   */
  public function applyOrderGoods($page = 1, $keyword = ''){

    $where = [];
    if($keyword) {
      $where['u.nickname|u.phone'] = ['like', "%{$keyword}%"];
    }

    $result = Db::name('apply_recharge')->alias('ar')->field('ar.*,u.nickname,u.phone,u.userimg,wx.nickname as wx_nickname, wx.headimgurl as wx_userimg')->join('__USER__ u', 'u.id = ar.user_id', 'left')->join('__USER_WEICHAT_INFO__ wx', 'wx.id=u.bindwx', 'left')->where($where)->order('ar.id desc')->paginate(Config::get('pageSize'), ['page' => $page, 'query' => ['keyword' => $keyword]]);

    return $this->fetch('index', ['list' => $result]);
  }

  /**
   * 审核服务中心的充值额度申请
   * @methods
   * @desc
   * @author slide
   *
   */
  public function doOrderGoods($id = ''){
    if($id) {
      $data = $this->request->post();
      if ( !isset( $data[ 'status' ] ) ) {
        return $this->ajax( 4000, '缺少必要参数' );
      }

      $result = Db::name( 'apply_recharge' )->where( 'id', $id )->find();

      if ( !$result ) {
        return $this->ajax( 4000, '没有这样的记录' );
      }

      $template_taskprocessing = cache_data( 'site_config' )[ 'template_taskprocessing' ];
      // 同意
      Db::startTrans();
      try {

        Db::name( 'apply_recharge' )->where( 'id', $id )->update( [ 'status' => $data[ 'status' ] ] );
        if ( $result && $data[ 'status' ] == 2 ) {
          Coupon::updateServiceCoin( $result[ 'user_id' ], 1, $result[ 'money' ], 1, '申请充值额度成功' );
        }
        Db::commit();
        $res = Message::taskNotice($result['user_id'], url( 'home/user/index'), '申请充值额度结果通知', '亲爱的服务中心您的充值额度申请已经通过，请及时登录平台查看！');
        return $this->ajax( 2000, '操作成功' );
      } catch ( \Exception $e ) {
        L( '后台操作审核充值额度失败' . $e );
        Db::rollback();
        Message::taskNotice($result['user_id'], url( 'home/user/index'), '申请充值额度结果通知', '亲爱的服务中心您的充值额度申请已被驳回，详情请咨询客服！');
        return $this->ajax( 4000, '操作失败' );
      }
    }else{
      return $this->ajax(4000,'缺少必要参数');
    }
  }

  /**
   * 配货券换货申请列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function goodsCouponToGoodsLits($page = 1){

    $list = model('service_center_coupon_to_goods')->where('coupon_ty', 1)->order('createtime desc')->paginate(Config::get('pageSize'), ['page' => $page]);

    return $this->fetch('goods_coupon_to_goods_list', ['list' => $list]);
  }

  /**
   * 配货券换货详情
   * @methods
   * @desc
   * @author slide
   *
   * @param $id
   *
   */
  public function goodsCouponToGoodsDetail($id){
    if(!$id)return $this->error('没有这样的信息');

    $detail = model('service_center_coupon_to_goods')->find($id);

    return $this->fetch('goods_coupon_to_goods_detail', ['detail' => $detail, 'shipping_config' => Config::get('shipping_config')]);
  }

  /**
   * 兑货券发货或者驳回
   * @methods
   * @desc
   * @author slide
   *
   */
  public function doGoodsCouponToGoods(){
    $data = $this->request->post();

    if(!isset($data['id']) || $data['id'] == '') return $this->ajax(4000, '缺少必要参数');
    $mdl = model('service_center_coupon_to_goods');
    // 发货&&驳回
    if($data['status'] == 2) {
      // 发货
      if((!isset($data['shipping_number']) || $data['shipping_number']='') && (!isset($data['shipping_code']) || $data['shipping_code'] == '') ) return $this->ajax(4000, '请先确认物流信息');
      $data['shipping_time'] = time();
      $mdl->data($data, true);
      $result = $mdl->allowField(true)->isUpdate(true)->save();
    }else{
      // $mdl->data($data, true);
      $result = $mdl->where('id', $data['id'])->update(['status' => $data['status']]);
    }

    if($result){
      return $this->ajax(2000, '操作成功');
    }else{
      return $this->ajax(4000, '操作失败');
    }
  }

  /**
   * 赠品券申请列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function giftCouponList($page = 1){
    $list = model('service_center_coupon_to_goods')->where('coupon_ty', 2)->order('createtime desc')->paginate(Config::get('pageSize'), ['page' => $page]);

    return $this->fetch('goods_coupon_to_goods_list', ['list' => $list]);
  }
}
