<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\common\model\Transport as trans;
use app\common\model\ShippingArea;

class Transport extends AdminBase
{
    protected $transMdl;
    protected $shipping;
    protected function _initialize(){
      parent::_initialize();
      $this->transMdl = new trans();
      $this->shipping = new ShippingArea();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $province = Db::name('region')->where(array('parent_id'=>0,'level'=>1))->select();
        $list = $this->transMdl->select();
        return $this->fetch('index', ['list'=>$list, 'province'=> $province]);
    }

    /**
     * [add 添加删除]
     * @Author:  slade
     * @DateTime :2017-03-28T17:09:46+080
     */
    public function add(){
      if($this->request->isPost()){
        $data = $this->request->post();
        $type = input('post.id') > 0 ? 2 : 1; //1插入2更新
        $this->transMdl->data($data, true);
        //$this->transMdl->status = $this->transMdl->status == '正常' ? 1 : 0;
        if($type == 1){
          $result = $this->transMdl->save();
          //生成默认的区域

          // "first_weight":"500","first_money":"0","second_weight":"500","second_money":"0"
          $area_data['first_weight'] = 1000;
          $area_data['first_money'] = 0;
          $area_data['second_weight'] = 1000;
          $area_data['second_money'] = 0;
          $shipping_area_data = [
            'transport_id'        => $this->transMdl->id,
            'shipping_area_name'  => '全国其他地区',
            'config'              => json_encode($area_data, true),
            'is_default'          => 1,
            'updatetime'          => time()
          ];
          $this->shipping->data($shipping_area_data, true);
          $this->shipping->allowField(true)->save();
          // dump($result);exit;
        }else{
          $result = $this->transMdl->isUpdate(true)->save();
        }
        if($result){
          $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }
      }else{
        $this->error('访问方式错误');
      }
    }

  /**
   * [getTransById 获取单个运费模版信息]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-28T17:10:09+080
   */
  public function getTransById($id = 0){
    if($id){
      $info = $this->transMdl->find($id);
      if($info){
        $this->success('获取运费模板成功', url('index'), $info);
      }else{
        $this->error('获取运费模板失败');
      }
    }else{
      $this->error('没有你要的运费模板');
    }
  }
  /**
   * [delete 删除运费模板]
   * @param    [type]                   $id [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:29:07+080
   */
  public function delete($id = 0, $ids = []) {

      adminLog('删除运费模板');
    $id = $ids ? $ids : $id;
    if($id){
      $this->request->get();
      if($this->transMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的运费模板');
    }
  }

  //==============================================================================
  /**
   * 运费模版的区域
   * @param    [type]                   $id [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-13T17:21:29+080
   */
  public function shippingarea($id){
    if($id){
      $res = $this->shipping->where("transport_id={$id}")->select();
      $province = Db::name('region')->where(array('parent_id'=>0,'level'=>1))->select();
      return $this->fetch('shippingarea_list', ['list'=>$res,'province'=>$province]);
    }else{
      $this->error('没有这样的运费模版');
    }
  }

  /**
   * 获取单个地区信息
   * @param    [type]                   $id [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-13T21:50:12+080
   */
  public function getshippingbyid($id){
    if($id){
      $res = $this->shipping->find($id);
      if($res){
        $this->success('获取地区信息成功','',$res);
      }else{
        $this->error('获取地区信息失败');
      }
    }else{
      $this->error('没有这样的地区');
    }
  }

  /**
   * 添加修改运费模版的区域信息
   * @Author:  slade
   * @DateTime :2017-04-13T17:25:43+080
   */
  public function addEditShippingArea(){
      
      adminLog('添加修改运费模版的区域信息');
      if($this->request->isPost()){
        $data = $this->request->post();
        $type = input('post.shipping_area_id') > 0 ? 2 : 1;
        //验证
        $validate_result = $this->validate($data, 'Shippingarea');
        if($validate_result !== true){
          $this->error($validate_result);
        }
        $this->shipping->data($data, true);
        if($type==1){
          $result = $this->shipping->allowField(true)->save();
        }else{
          $result = $this->shipping->allowField(true)->isUpdate(true)->save();
        }
        // exit;
        if($result){
          $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }
      }else{
        $this->error('请求方式错误');
      }
  }

  /**
   * [delete 删除运费模板区域]
   * @param    [type]                   $id [description]
   * @return   {[type]                  [description]
   * @Author:  slade
   * @DateTime :2017-03-23T14:29:07+080
   */
  public function deletearea($id = 0, $ids = []) {

      adminLog('删除运费模板区域');
    $id = $ids ? $ids : $id;
    if($id){
      $this->request->get();
      if($this->shipping->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的运费模板区域');
    }
  }
}
