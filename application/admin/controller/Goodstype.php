<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\common\model\GoodsModel;

class Goodstype extends AdminBase
{
    protected $typeMdl;
    protected function _initialize() {
        parent::_initialize();
        $this->typeMdl = new GoodsModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $list = $this->typeMdl->select();
        return $this->fetch('index', ['list'=>$list]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
      if($this->request->isPost()){
        $data = $this->request->post();
        $data['createtime'] =time();
        if($this->typeMdl->save($data)){
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
          $info = $this->typeMdl->find($id);
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
    public function update(Request $request, $id)


    {
      if($this->request->isPost()){
        $data = $this->request->post();
        // dump($data);exit;
        $type           = $this->typeMdl->find($data['id']);
        $type->id       = $data['id'];
        $type->name    = isset($data['name']) ? $data['name']: $type['name'];
        if($type->save()){
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

        adminLog('删除商品规格');
      $id = $ids ? $ids : $id;
      if($id){
        $this->request->get();
        if($this->typeMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的会员');
      }
    }
}
