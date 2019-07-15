<?php

namespace app\admin\controller;

use think\Request;
use think\Config;
use think\Db;
use app\common\model\Address as AddressModel;
/**
 * [index 收货地址]
 * @return   {[type]                  [description]
 * @Author:  slade
 * @DateTime :2017-03-23T16:09:31+080
 */
class Address extends AdminBase
{
    protected $addressMdl;
    protected function _initialize(){
      parent::_initialize();
      $this->addressMdl = new AddressModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($keywords='', $page = 1)
    {
      $map = [];
      if($keywords) {
        $map['name|phone|uid'] = ['LIKE', "%{$keywords}%"];
      }
      $list = $this->addressMdl->where($map)->order('id desc')->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      // dump($this->addressMdl->getLastSql());
      $page = $list->render();
      // dump($list->toArray());
      return $this->fetch('index', ['list' => $list->toArray(),'page'=>$page, 'keywords' => $keywords]);
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id=0,$ids = [])
    {      adminLog('删除地址');
        $id = $id ? $id : $ids;
        if($id){
          $this->request->get();
          if($this->addressMdl->destroy($id)){
            $this->success('删除成功');
          }else{
            $this->error('删除失败');
          }
        }else{
          $this->error("请选择需要删除的项");
        }
    }
}
