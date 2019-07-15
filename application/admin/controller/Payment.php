<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\common\model\Payment as payModel;
// use app\common\validate?

class Payment extends Controller
{
    protected $payMdl;
    public function _initialize(){
      parent::_initialize();
      $this->payMdl = new payModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $list = $this->payMdl->select();

        return $this->fetch('index', ['list'=>$list]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function addEdit()
    {
        if($this->request->isPost()){
          $data = $this->request->post();
          $type = input('post.id') > 0 ? 2 : 1; //1插入2更新
          $validate_result = $this->validate($data,'Payment');
          if(!$validate_result){
            $this->error($validate_result);
          }else{
            $this->payMdl->data($data, true);
            if($type == 1){
              $result = $this->payMdl->save();
            }else{
              $result = $this->payMdl->isUpdate(true)->save();
            }
            if($result){
              $this->success('操作成功');
            }else{
              $this->error('操作失败');
            }
          }

        }else{
          $this->error('访问方式不正确');
        }
    }

    public function getPayById($id = 0){
      if($id){
        $info = $this->payMdl->find($id);
        if($info){
          $this->success('查询成功', '', $info);
        }else{
          $this->error('没有你选择的支付方式');
        }
      }else{
        $this->error('没有你选择的支付方式');
      }
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
     public function delete($id = 0, $ids = []) {
       $id = $ids ? $ids : $id;
       if($id){
         $this->request->get();
         if($this->payMdl->destroy($id)){
           $this->success('删除成功');
         }else{
           $this->error('删除失败');
         }
       }else{
         $this->error('请选择需要删除的支付方式');
       }
     }
}
