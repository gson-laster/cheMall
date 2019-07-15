<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19 0019
 * Time: 下午 5:28
 */

namespace app\admin\controller;

use app\common\model\Actively as ActivelyModel;
use app\common\model\Goods;
class Actively extends AdminBase {
  
  protected $activeMdl;
  
  protected function _initialize(){
    parent::_initialize();
    $this->activeMdl = new ActivelyModel();
  }
  
  /**
   * 活动列表
   * @author: slide
   */
  public function index () {
    $list = $this->activeMdl->select();
    $goods = (new Goods())->field(['id', 'title', 'thumb'])->select();
    return $this->fetch('index', ['list' => $list, 'goods' => $goods]);
  }
  
  /**
   * 获取单个活动
   * @author: slide
   * @param string $id
   */
  public function getActivelyById($id = ''){
    if(!$id) return $this->error('没有你要的活动信息');
    if($res = $this->activeMdl->find($id)){
      return $this->ajax(2000, '获取成功', '', $res);
    }else{
      return $this->ajax(4000, '获取失败');
    }
  }
  
  /**
   * 保存活动
   * @author: slide
   *
   */
  public function saveActively(){
    if($this->request->isPost()){
      $data = $this->request->post();
      $type = input('id') ? 2 : 1;
      $data['createtime'] = time();
      $this->activeMdl->data($data, true);
      if($type == 1) {
        $result = $this->activeMdl->isUpdate(false)->allowField(true)->save();
      }else{
        $result = $this->activeMdl->isUpdate(true)->allowField(true)->save();
      }
      if($result){
        return $this->ajax(2000, '操作成功');
      }else{
        return $this->ajax(4000, '操作失败');
      }
    }else{
      $this->error('请求的方式错误');
    }
  }
  
  /**
   * 删除
   * @author: slide
   * @param int   $id
   * @param array $ids
   *
   */
  public function delete($id = 0, $ids = []) {
    $id = $ids ? $ids : $id;
    if($id){
      $this->request->get();
      if($this->activeMdl->destroy($id)){
        $this->success('删除成功');
      }else{
        $this->error('删除失败');
      }
    }else{
      $this->error('请选择需要删除的会员');
    }
  }
}
