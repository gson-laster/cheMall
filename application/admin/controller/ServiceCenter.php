<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 下午 4:42
 */

namespace app\admin\controller;
use think\Config;
use think\Db;
/**
 * 服务中心管理
 * Class ServiceCenter
 *
 * @package app\admin\controller
 */
class ServiceCenter extends AdminBase {
  protected $serviceMdl = '';

  protected function _initialize(){
    $this->serviceMdl = new \app\common\model\ServiceCenter();
  }

  /**
   * 列表
   * @methods
   * @desc
   * @author slide
   *
   */
  public function index ($page = 1, $keyword = '') {
    $where = [];
    if($keyword) {
      $where['title'] = ['like', '%'.$keyword.'%'];
    }

    $list = $this->serviceMdl->where($where)->order('zid desc')->paginate(Config::get('pageSize'), ['page' => $page, 'query' => ['keyword' => $keyword]]);

    return $this->fetch('index', ['list' => $list, 'keyword' => $keyword]);
  }

  /**
   * 添加/修改页面
   * @methods
   * @desc
   * @author slide
   *
   */
  public function add($id = ''){
    if($id) {
      $service = $this->serviceMdl->find($id);
      $images = Db::name('service_center_images')->where('service_id', $id)->select();
      $detail = Db::name('service_center_detail')->where('service_id', $id)->find();
      $this->assign('service', $service);
      $this->assign('images', $images);
      $this->assign('detail', $detail);
    }

    return $this->fetch();
  }

  /**
   * 处理添加或者编辑的方法
   * @methods
   * @desc
   * @author slide
   *
   */
  public function doPost(){
    if($this->request->isPost()){
      $data = $this->request->post();
      $this->serviceMdl->data($data, true);
      $id = '';
      if(isset($data['id'])) {
        $result = $this->serviceMdl->allowField(true)->isUpdate(true)->save();
        $id = $data['id'];
      }else{
        $result = $this->serviceMdl->allowField(true)->isUpdate(false)->save();
        $id = $this->serviceMdl->id;
      }

        // 处理图片和详情
      $res = $this->serviceMdl->afterSaveHandle($id, $data['content'], $data['images']);
      if($result || $res){
        return $this->ajax(2000, '操作成功');
      }else{
        return $this->ajax(4000, '操作失败');
      }

    }else{
      return $this->error('访问方式错误');
    }
  }

  /**
   * 删除记录
   * @methods
   * @desc
   * @author slide
   *
   */
  public function deletes($ids = ''){
    if(!$ids) return $this->ajax(4000, '删除失败');

    if($this->serviceMdl->where('1=1')->delete($ids)){
      return $this->ajax(2000, '删除成功');
    }else{
      return $this->ajax(4000, '删除失败');
    }
  }
}
