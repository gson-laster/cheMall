<?php

namespace app\admin\controller;

use think\Request;
use think\Config;
use think\Db;
use app\common\model\GoodsCategory as CateModel;

class Category extends AdminBase
{
    protected $cateMdl;
    protected function _initialize() {
        parent::_initialize();
        $this->cateMdl = new CateModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($keywords = '', $page=1)
    {
      $map = [];
      if($keywords){
        $map['name'] = ['LIKE', "%'{$keywords}'%"];
      }
      $list = $this->cateMdl->getLevelList();
      cache_data('goods_category', $list);

      return $this->fetch('index', ['list'=>$list, 'keywords', $keywords]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        adminLog('添加商品分类');
      if($this->request->isPost()){
        $data = $this->request->post();
        if($this->cateMdl->save($data)){
          $this->success('添加成功');
        }else{
          $this->error('添加失败');
        }
      }else{
        $this->error('访问方式错误');
      }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function getCateById($id)
    {
        if($id){
          $info = $this->cateMdl->find($id);
          if($info){
            $this->success('查询成功',url('index'), $info);
          }else{
            $this->error('查询失败');
          }
        }else{
          $this->error('请选择分类');
        }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update()


    {
        adminLog('更新商品分类');
        if($this->request->isPost()){
          $data = $this->request->post();
          // dump($data);exit;
          $cate           = $this->cateMdl->find($data['id']);
          $cate->id       = $data['id'];
          $cate->name    = isset($data['name']) ? $data['name']: $cate['name'];
          $cate->short_name    = isset($data['short_name']) ? $data['short_name']: $cate['short_name'];
          $cate->zid    = isset($data['zid']) ? $data['zid']: $cate['zid'];
          $cate->pid     = isset($data['pid']) ? $data['pid']: $cate['pid'];
          $cate->status   = isset($data['status']) ? $data['status'] : $cate['status'];
          $cate->is_show_index   = isset($data['is_show_index']) ? $data['is_show_index'] : $cate['is_show_index'];
          if($cate->save()){
            $this->success('更新成功');
          }else{
            $this->error('更新失败');
          }
        }else{
          $this->error('访问方式错误');
        }
    }

    /**
     * [delete 删除]
     * @param    [type]                   $id [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-23T14:29:07+080
     */
    public function delete($id = 0, $ids = []) {

        adminLog('删除商品分类');
      $id = $ids ? $ids : $id;
      if($id){
        $this->request->get();
        if($this->cateMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的会员');
      }
    }
}
