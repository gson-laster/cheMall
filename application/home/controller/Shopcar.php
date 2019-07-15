<?php

namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\common\model\Shopcar as ShopModel;
use app\common\model\Spec;
use app\common\model\SpecItem;
use app\common\model\Goods;
use think\Db;
class Shopcar extends Validate
{
  protected $shopMdl;
  protected function _initialize(){
    parent::_initialize();
    $this->shopMdl = new ShopModel();
  }

  /**
   * 购物车列表
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-04-08T11:00:07+080
   */
  public function index () {
    $list = [];
    if(Session::get('qt_userId')){
      $userId = Session::get('qt_userId');
      $list = $this->shopMdl->alias('s')->field(['s.id','s.goods_id','s.user_id','s.artnum','s.goods_price','s.goods_name','s.spec_config','s.selected','s.number','g.goods_type','g.mkt_price'])->join("__GOODS__ g",'g.id=s.goods_id','LEFT')->where("user_id={$userId} AND g.is_on_sale=1")->select();
      //dump($this->shopMdl->getLastSql());
      //商品规格
      $goodsSpec = new Spec();
      $goods_spec = $goodsSpec->field(['id','name','type_id'])->order('zid DESC')->select();
      foreach ($list as $key => $value) {
        $temp_spec = [];
        foreach ($goods_spec as $k => $v) {
          if($value['goods_type'] == $v['type_id']){
            $temp_spec[] = $v;
          }
        }
        $value['thumb'] = goods_thum_images($value['goods_id'], 200, 200);
        $value['spec'] = $temp_spec;
        // dump($goods_spec);
        //规格参数
        if($value['spec_config'] != ''){
          $goods_spec_item = new SpecItem();
          $spec_item_ids = explode('|', $value['spec_config']);
          $goods_spec_item_res = [];
          // dump($value['spec_config']);
          if(count($spec_item_ids)){
            $goods_spec_item_res = $goods_spec_item->where("id IN (".implode(',',$spec_item_ids).") ")->select();
          }
          $value['spec_item'] = $goods_spec_item_res;
        }

      }
      //dump($list);
    }
    return $this->fetch('index', [
      'list'            =>  $list
    ]);
  }

  /**
   * 添加购物车
   * @Author:  slade
   * @DateTime :2017-04-08T11:05:08+080
   */
  public function add(){
    if($this->request->isPost()){
      $goods_id = input('post.goods_id');
      $userId = Session::get('qt_userId');
      if(!$goods_id){
        $this->error('没有选择商品');
      }else{
        $data = $this->request->post();
        $data['user_id'] = $userId;
        $validate_result = $this->validate($data, 'Shopcar');
        if(!$validate_result){
          $this->error($validate_result);
        }
        $res = $this->shopMdl->where("user_id='{$userId}' AND goods_id='{$goods_id}' AND spec_config='".$data['spec_config']."'")->find();
        $shop_car_num = Session::get( 'qt_userId' ) ? Db::name( 'shopcar' )->where( 'user_id=' . Session::get( 'qt_userId' ) )->count() : 0;
        // dump($this->shopMdl->getLastSql());
        // dump($res);exit;
        if($res){
          //有则数量+1
          $this->shopMdl->where("user_id='{$userId}' AND goods_id='{$goods_id}' AND spec_config='".$data['spec_config']."'")->setInc('number', 1);
          $this->success('添加购物车成功','', ['id'=>$res['id'],'shopnum'=>$shop_car_num]);
        }else{

          $this->shopMdl->data($data, true);
          $this->shopMdl->user_id = $userId;

          if($this->shopMdl->allowField(true)->save()){
            $this->success('添加购物车成功','', ['id'=>$this->shopMdl->id, 'shopnum'=>$shop_car_num]);
          }else{
            $this->error('添加购物车失败');
          }
        }
      }
    }else{
      $this->error('访问方式错误');
    }
  }

  /**
   * 更新购物车
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-04-08T11:10:05+080
   */
  public function update($id = 0, $goods_id = 0){
    if($goods_id && $id){
      $data = input();
      $this->shopMdl->data($data, true);
      if($this->allowField(true)->isUpdate(true)->save()){
        $this->success('操作成功过');
      }else{
        $this->error('操作失败');
      }
    }else{
      $this->error('请选择需要更改的记录');
    }
  }

  /**
   * 校验商品库存量
   * @param    [type]                   $gid [description]
   * @param    [type]                   $num [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-11T16:41:21+080
   */
  public function checkGoodsStoreCount($carid, $gid, $num){
    if(!$gid || !$num || !$carid){
      $this->error('缺少必要参数');
    }else{
      $car_res = $this->shopMdl->field(['spec_config'])->where("id={$carid}")->find()['spec_config'];
      $goods = new Goods();
      $res = $goods->field(['store_count','spec_config'])->find($gid);
      $spec_config = json_decode($res->spec_config, true);
      $car_res_spec = implode('_', explode('|', $car_res));
      // dump($carid);
      // dump($car_res);
      // dump($car_res_spec);
      // dump($spec_config[$car_res_spec]);
      // dump($this->shopMdl->getLastSql());exit;
      if(isset($spec_config[$car_res_spec]) && !empty($spec_config[$car_res_spec])){
        $strore_count = $spec_config[$car_res_spec]['store_count'];
        //dump($strore_count);
      }else{
        $strore_count = $res['store_count'];
      }
      //dump($strore_count);
      if($strore_count - $num > 0){
        return $this->ajax(1002, '库存充足','',['store_count'=>$strore_count]);
      }else{
        return $this->ajax(1001, '库存不足','',['store_count'=>$strore_count]);
      }
    }
  }
  /**
   * 删除记录
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-11T16:34:46+080
   */
  public function delete($ids = ''){
    if($ids != ''){
      //$this->shopMdl->destroy($id);
      //dump($this->shopMdl->getLastSql());
      // exit;
      $id = explode(',',$ids);
      // $userId = Session::get('qt_userId');
      if($this->shopMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的记录');
    }
  }
}
