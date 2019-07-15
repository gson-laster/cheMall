<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\common\model\Spec;
use app\common\model\GoodsModel;
use app\common\model\SpecItem;
use think\Config;

class Speclist extends Controller
{
    protected $specMdl;
    protected $GoodsMdl;
    protected function _initialize(){
      parent::_initialize();
      $this->specMdl = new Spec();
      $this->goodsMdl = new GoodsModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($page=1)
    {
        $list = $this->specMdl->order('zid DESC')->paginate(Config::get('pageSize'), false, ['page'=>$page]);
        $type = $this->goodsMdl->select();
        $items = new SpecItem();
        foreach($list as $k => &$v)
        {       // 获取规格项
                $arr = $items->getSpecItem($v['id']);
                $v['spec_item'] = implode(' , ', $arr);
        }
        // dump($type[9]);
        return $this->fetch('index', ['list'=>$list, 'type'=>$type]);
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
        //验证
        $validate_result = $this->validate($data, 'Spec');
        if ($validate_result !== true) {
            $this->error($validate_result);
        } else {
          // dump($data);exit/;
          $this->specMdl->name = $data['name'];
          $this->specMdl->zid  = $data['zid'];
          $this->specMdl->type_id = $data['type_id'];
          $inserId = $this->specMdl->save(); // 写入数据到数据库
          $insert_id = $this->specMdl->id;
          $this->specMdl->afterSave($insert_id, $data['items']);
          $this->success('保存成功');
        }
      }
    }

    /**
     * [update 更新保存]
     * @param    [array]                   $user [description]
     * @return   {[mixed]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-23T14:04:30+080
     */
    public function update($id = 0){
        adminLog('更新商品规格');
      if($id){
        $data = $this->request->post();
        $spec           = $this->specMdl->find($data['id']);
        $spec->id       = $data['id'];
        $spec->name    = isset($data['name']) ? $data['name']: $spec['name'];
        $spec->type_id    = isset($data['type_id']) ? $data['type_id']: $spec['type_id'];
        $spec->zid     = isset($data['zid']) ? $data['zid']: $spec['zid'];
        $res = $spec->save();
        // dump($user->getLastSql());exit;
        if ($res !== false) {
          $insert_id = $spec->id;
          $this->specMdl->afterSave($insert_id, $data['items']);
          $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
      }else{
        $this->error('缺少必要参数');
      }
    }

    /**
     * [getuserbyid 根据iid返回规格信息]
     * @param    [type]                   $id [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-23T14:57:05+080
     */
    public function getInfobyid($id){
      if($id){
        $spec = $this->specMdl->find($id);

        $GoodsLogic = new SpecItem();
        $items = $GoodsLogic->getSpecItem($id);
        $spec['items'] = implode(',', $items);


        // dump($spec);
        if($spec){
          $this->success('查询成功', url('index'), $spec);
        }else{
          $this->error('查询失败');
        }
      }else{
        $this->error("缺少必要参数");
      }
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
     public function delete($id = 0, $ids = []) {

         adminLog('删除商品规格');
       $id = $ids ? $ids : $id;
       if($id){
         $this->request->get();
         if($this->specMdl->destroy($id)){
           $this->success('删除成功');
         }else{
           $this->error('删除失败');
         }
       }else{
         $this->error('请选择需要删除的会员');
       }
     }
}
