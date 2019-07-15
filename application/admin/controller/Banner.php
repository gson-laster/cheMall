<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Config;
use app\common\model\Banner as BannerModel;

class Banner extends AdminBase
{
    protected $bannerMdl;
    protected function _initialize(){
      parent::_initialize();
      $this->bannerMdl = new BannerModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($page = 0)
    {
      $list = $this->bannerMdl->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      return $this->fetch('index',['list'=>$list]);
    }

    public function getBannerById($id=0){
      if($id){
        $info = $this->bannerMdl->find($id);
        if($info){
          $this->success('获取成功', url('index'), $info);
        }else{
          $this->error('获取失败');
        }
      }else{
        $this->error('没有这样的轮播');
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
        adminLog('更新轮播图');
        if($this->request->isPost()){
          $data = $this->request->post();
          $type = input('id') > 0 ? 2 : 1;
          $this->bannerMdl->data($data, true);
          if($type == 1){
            $result = $this->bannerMdl->save();
          } else {
            $result = $this->bannerMdl->isUpdate(true)->save();
          }
          if($result){
            $this->success('操作成功', url('index'));
          } else {
            $this->error('操作失败');
          }
        }else{
          $this->error('访问方式错误');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id=0,$ids = [])
    {
        adminLog('删除轮播图');
      $id = $ids ? $ids : $id;
      if($id){
        $this->request->get();
        if($this->bannerMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的会员');
      }
    }
}
