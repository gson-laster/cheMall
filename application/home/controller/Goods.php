<?php
namespace app\home\controller;

use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsImages;
use app\common\model\GoodsDetails;
use app\common\model\Payment;
use app\common\model\GoodsAttribute;
use app\common\model\Spec;
use app\common\model\SpecItem;
use think\Db;
use think\Session;

/**
 * 前台商品
 */
class Goods extends HomeBase {
  protected $goodsMdl;
  
  protected function _initialize () {
    parent::_initialize();
    $this->goodsMdl = new GoodsModel();
  }
  
  /**
   * 商品展示页
   *
   * @param    integer $gid [description]
   *
   * @return   {[type]                  [description]
   * @Author   :  slade
   * @DateTime :2017-04-07T09:10:55+080
   */
  public function index ( $gid = 0 ) {
    if ( $gid ) {
      //商品轮播
      $gImage       = new GoodsImages();
      $goods_images = $gImage->where( "goods_id={$gid}" )->select();
      //商品基本信息
      $goods_info = $this->goodsMdl->field( [ 'id', 'title', 'shop_price', 'buy_num', 'payment', 'spec_config', 'goods_attr_config', 'comment_num', 'is_on_sale', 'goods_type', 'artnum', 'brand', 'weight', 'unit', 'on_time', 'store_count','mkt_price', 'store_profit', 'keywords', 'description' ] )->find( $gid );
      if ( !$goods_info ) {
        $this->error( '没有这样的商品', url( 'home/index/index' ) );
      }
      if($goods_info['store_count'] <= 0){
        $this->error('该商品库存不足');
      }
      $goods_info->spec_config       = $goods_info[ 'spec_config' ];
      $goods_info->goods_attr_config = json_decode( $goods_info->goods_attr_config, true );
      
      //本店支持的支付方式
      $payment      = new Payment();
      $payment_list = $payment->field( [ 'id', 'name' ] )->where( 'status=1' )->select();
      //商品详情
      $detail        = new GoodsDetails();
      $goods_details = $detail->where( "goods_id={$gid}" )->find();
      
      //更新访问次数
      $this->goodsMdl->where( "id={$gid}" )->setInc( 'click_count', 1 );
      
      //是否收藏过
      if ( Session::get( 'qt_userId' ) ) {
        $userId     = Session::get( 'qt_userId' );
        $is_collect = Db::name( 'collect' )->where( "goods_id={$gid} AND user_id={$userId}" )->find() ? true : false;
      } else {
        $is_collect = false;
      }
      //商品属性列表
      $goodsAttr       = new GoodsAttribute();
      $goods_attr      = $goodsAttr->where( "type_id=" . $goods_info[ 'goods_type' ] )->select();
      $goods_attr_list = [];
      foreach ( $goods_attr as $key => $value ) {
        $temp[ 'attr_name' ]  = $value[ 'attr_name' ];
        $temp[ 'attr_value' ] = $goods_info->goods_attr_config[ $value[ 'attr_id' ] ];
        $goods_attr_list[]    = $temp;
      }
      // dump($goods_attr_list);
      $spec_config_arr = json_decode( $goods_info->spec_config, true );
      // dump($spec_config_arr);
      //商品规格
      $goodsSpec  = new Spec();
      $goods_spec = $goodsSpec->field( [ 'id', 'name' ] )->where( "type_id=" . $goods_info[ 'goods_type' ] )->order( 'zid DESC' )->select();
      // dump($goods_spec);
      //规格参数
      $goods_spec_item = new SpecItem();
      $spec_item_ids   = [];
      if ( $spec_config_arr ) {
        foreach ( $spec_config_arr as $k => $v ) {
          $temp_ids = explode( '_', $k );
          foreach ( $temp_ids as $key => $value ) {
            array_push( $spec_item_ids, $value );
          }
        }
      }
      
      $goods_spec_item_res = [];
      if ( count( $spec_item_ids ) ) {
        $goods_spec_item_res = $goods_spec_item->where( "id IN (" . implode( ',', $spec_item_ids ) . ") " )->select();
      }
      // dump($goods_spec_item_res);exit;
      // dump($goods_info);
      //品牌
      $goods_brand = Db::name( 'brand' )->field( [ 'id', 'name' ] )->find( $goods_info[ 'brand' ] );
      
      //购物车数量
      $shop_car_num = Session::get( 'qt_userId' ) ? Db::name( 'shopcar' )->where( 'user_id=' . Session::get( 'qt_userId' ) )->count() : 0;
      
      //轮播图
      $banner_list = Db( 'goods_images' )->where( "goods_id=" . $goods_info[ 'id' ] )->select();
  
      $service = Session::get('userInfo') ? Db::name('agent_employee')->where(['agent_id'=>Session::get('userInfo')['parent_agent'],'is_service'=>1])->find() : 0;
      
      if($service){
        $server_id = $service['msg_uid'];
      }else{
        $server_id = '-2';
      }
      
      return $this->fetch( 'index', [ 'goods_images' => $goods_images, 'ginfo' => $goods_info, 'payment_list' => $payment_list, 'goods_details' => $goods_details, 'is_collect' => $is_collect, 'goods_attr' => $goods_attr_list, 'goods_spec' => $goods_spec, 'spec_itm' => $goods_spec_item_res, 'shop_car_num' => $shop_car_num, 'goods_brand' => $goods_brand, 'goods_banner' => $banner_list,'sever_uid'=>$server_id ] );
    } else {
      $this->error( '没有这样的商品', url( 'home/goodsList' ) );
    }
  }
  
  /**
   * ajax 收藏商品
   *
   * @param    integer $gid [description]
   *
   * @return   {[type]                  [description]
   * @Author   :  slade
   * @DateTime :2017-04-07T14:17:27+080
   */
  public function ajaxAddCollect ( $gid = 0, $type = '' ) {
    if ( $gid && $type ) {
      $userId = Session::get( 'qt_userId' );
      if ( $type == 'delete' ) {
        $res = Db::name( 'collect' )->where( "goods_id={$gid} AND user_id={$userId}" )->delete();
        if ( $res ) {
          $this->success( '取消收藏成功' );
        } else {
          $this->error( "取消收藏失败" );
        }
      } else if ( $type == 'add' ) {
        //收藏
        if ( !Session::get( 'qt_userId' ) ) {
          $this->error( '你还没有登录', url( 'login/index' ) );
        } else {
          $data = [ "goods_id" => $gid, "user_id" => Session::get( 'qt_userId' ), 'createtime' => time() ];
          if ( Db::name( 'collect' )->insert( $data ) ) {
            $this->success( '收藏成功' );
          } else {
            $this->error( '收藏失败' );
          }
        }
      }
    } else {
      $this->error( '没有这样的商品' );
    }
  }
  
  /**
   * 获取类商品
   *
   * @param    [string]                   $type [new 新品，hot 热卖，recommend 推荐]
   *
   * @return   [type]                   [description]
   * @Author   :  slade
   * @DateTime :2017-04-28T10:10:20+080
   */
  public function getGoodsType ( $type, $pageSize = 10, $page = 1 ) {
    $goods_list = $this->goodsMdl->where( "is_{$type}=1 AND is_on_sale=1" )->order( 'createtime DESC' )->paginate( $pageSize, false, [ 'page' => $page ] );
    if ( $goods_list ) {
      $this->success( '查询成功', '', $goods_list );
    } else {
      $this->error( '查询失败' );
    }
  }
  public function goods_detail(){
    return $this->fetch('goods_detail'); 
  }
}
?>
