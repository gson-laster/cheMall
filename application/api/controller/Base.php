<?php

namespace app\api\controller;

use app\common\model\Order;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;

/**
 * 通用api
 */
class Base extends Controller
{
    protected function _initialize(){
      parent::_initialize();
    }
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeStatu(){

      $table = input('get.table'); // 表名
      $id_name = input('get.id_name'); // 表主键id名
      $id_value = input('get.id_value'); // 表主键id值
      $field  = input('get.field'); // 修改哪个字段
      $value  = input('get.value'); // 修改字段值
      $mdl = Db::name($table);
      $mdl->data([$field=>$value]);
      $res = $mdl->where("$id_name = $id_value")->update(); // 根据条件保存修改的数据

      if($res){
        $this->success('修改成功');
      }else{
        $this->error('修改失败');
      }
    }

    public function ajax($code = 0, $msg = '', $url = '', $data = []){
  			$data = [
  				"code" => $code,
  				"msg"	 => $msg,
  				'url'	 => $url,
  				'data' => $data
  			];
        return json($data);
  	}

    /**
     * 获取省
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-21T11:18:50+080
     */
    public function getProvince () {
      $data = Db::name('region')->where("level", 1)->select();
      return json($data);
    }

    /*
     * 获取地区
     */
    public function getRegion($type = 'html'){
        $parent_id = input('parent_id');
        $data = Db::name('region')->where("parent_id", $parent_id)->select();
        // dump($parent_id);
        $html = '';
        if($data){
            foreach($data as $h){
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        if($type == 'html'){
          echo $html;
        }else{
          return json($data);
        }
    }
    /**
     * [getTwon shiji]
     * @return   {[type]                  [html返回select的option，json 返回json数据]
     * @Author:  slade
     * @DateTime :2017-03-28T15:05:50+080
     */
    public function getTwon($type='html'){
    	$parent_id = input('get.parent_id/d');
    	$data = Db::name('region')->where("parent_id",$parent_id)->select();
    	$html = '';
    	if($data){
    		foreach($data as $h){
    			$html .= "<option value='{$h['id']}'>{$h['name']}</option>";
    		}
    	}
    	if(empty($html)){
    		echo '0';
    	}else{
    		if($type == 'html'){
          echo $html;
        }elseif($type == 'json'){
          return json($data);
        }
    	}
    }

    /**
     * 获取地区
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-13T14:58:36+080
     */
    public function get_area_list(){
      $region  = Db::name('region');
      $region1 = $region->where("level=1")->select();
      $region2 = $region->where("level=2")->select();
      $region3 = $region->where("level=3")->select();
      $region4 = $region->where("level=4")->select();
      $result = [];
      // return json(['region1'=>$region1,'region2'=>$region2,'region3'=>$region3,'region4'=>$region4]);
      // dump(json($region1));
      // dump(json($region2));
      // dump(json($region3));
      // dump(json($region4));exit;
      foreach ($region1 as $key => $value) {
        foreach ($region2 as $k => $v) {
          foreach ($region3 as $m => $vm) {
            foreach ($region4 as $n => $vn) {
              if($vm['id'] == $vn['parent_id']){
                $vm['child'][] = $vn;
              }
            }
            if($v['id'] == $vm['parent_id']){
              $v['child'][] = $vm;
            }
          }
          if($value['id'] == $v['parent_id']){
            $value['child'][] = $v;
          }
        }
        $result[]=$value;
      }
      return json($result);
    }
    public function get_area_all(){
      $region  = Db::name('region')->select();
      return json(convert_arr_key($region, 'id'));
    }
    /**
     * 检验是否登录
     * @return   {boolean                 [description]
     * @Author:  slade
     * @DateTime :2017-04-08T09:27:15+080
     */
    public function isLogin(){
      if(Session::get('qt_userId')){
        return $this->ajax(1002,'已经登录');
      }else{
        return $this->ajax(1001, '还没有登录', url('home/login/index'));
      }
    }
    /**
     * 获取购物车的数量
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-04-08T14:25:00+080
     */
    public function getShopCarNum(){
      if(Session::get('qt_userId')){
        $num = Db::name('shopcar')->where('user_id='. Session::get('qt_userId'))->count();
        return $this->ajax(1002, '获取成功', '', ['num'=>$num]);
      }else{
        return $this->ajax(1001, '获取失败');
      }
    }
    /**
     * 获取代理类型
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-15T10:30:43+080
     */
    public function getAgentType($id){
      if($id){
        $result = Db::name('agent')->find($id);
      }else{
        $result = Db::name('agent')->select();
      }

      return json($result);
    }
    /**
     * 获取用户信息
     * @return   [Info]                   [description]
     * @Author:  slade
     * @DateTime :2017-05-15T10:30:43+080
     */
    public function getUser($id){
      if($id){
        $result = Db::name('user')->find($id);
      }else{
        $result = Db::name('user')->select();
      }
      return json($result);
    }

  /**
   * 获取所有的省级代理
   * @author: slide
   *
   */
    public function getFirstAgent(){
      return json(Db::name('user')->where('agent_type', 1)->select());
    }

  /**
   * 获取所有的代理
   * @methods
   * @desc
   * @author slide
   * @return \think\response\Json
   *
   */
    public function getUserAgent(){
      $result = model('user')->where(['agent_type' => ['<>', 0]])->select();
      return $this->ajax(2000, 'success', '', $result);
    }

  /**
   * 获取代理员工客服信息
   * @author: slide
   * @param $agent_id
   */
    public function getService($agent_id){
      return Db::name('agent_employee')->where(['is_service'=>1, 'agent_id'=>$agent_id])->find();
    }

  /**
   * 获取商品信息
   * @author: slide
   * @param        $ids
   * @param string $field
   *
   */
    public function getGoods($ids, $field=""){
      return Db::name('goods')->field($field)->where("id IN ($ids)")->select();
    }

  /**
   * 改变数量（未考虑库存）
   * @author: slide
   * @param $order_id
   * @param $goods_id
   * @param $num
   * @return \think\response\Json
   *
   */
    public function changeOrderNum($order_id, $goods_id, $num){
      $res = Db::name('order_goods')->where(['order_id' => $order_id, 'goods_id'=>$goods_id])->find();
      if(!$res) return $this->ajax(4000, '没有这样的商品');
      $total = $res['goods_num'] + $num;
      $data = [
        'goods_total' => $total * $res['goods_price'],
        'goods_num' => $total,
      ];
      if(Db::name('order_goods')->where(['order_id' => $order_id, 'goods_id'=>$goods_id])->update($data)){
        return $this->ajax(2000, '更新成功');
      }else{
        return $this->ajax(4000, '更新失败');
      }
    }

  /**
   * 获取订单商品
   * @author: slide
   * @param $order_id
   * @return \think\response\Json
   *
   */
    public function getOrderGoods($order_id){
      return $this->ajax(2000, '商品获取成功', '', (new Order())->getOrderGoods($order_id));
    }
}
