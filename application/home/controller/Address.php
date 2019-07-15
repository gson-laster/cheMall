<?php

namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\common\model\Address as AddressModel;
use app\common\model\User;

class Address extends Validate
{
    protected $addressMdl;
    protected function _initialize(){
    parent::_initialize();
      $this->addressMdl = new AddressModel();
    }
    /**
     *  用户收货地址列表
     * @return \think\Response
     */
    public function index()
    {

      $address_res = $this->addressMdl->where("uid=".Session::get('qt_userId'))->select();
      // dump($address_res);
      $user = User::get(Session::get('qt_userId'));
      $address_now = $user->address_now;
      if($this->request->isAjax()){
        return $this->ajax(1002, '查询成功', '',convert_arr_key($address_res, 'id') );
      }
      return $this->fetch('index', ['address_list'=>$address_res, 'address_now'=>$address_now]);
    }

    /**
     * 添加收货地址
     * @return \think\Response
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存收货地址
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save()
    {
        // dump();exit;
        if($this->request->isPost()){
          $data = $this->request->post();
          $data['uid'] = isset($data['uid']) ? $data['uid'] : Session::get('qt_userId');
          //dump($data);exit;
          // $data['??']
          $validate_result = $this->validate($data, 'Address');
          if(!$validate_result){
            $this->error($validate_result);
          }else{
            $data['createtime'] = time();
            $res = Db::name('address')->insert($data);
            $last_id = Db::name('address')->getLastInsID();
            if($res){
              $url = input("get.backurl") ? "?".input("get.backurl") : '';
              $this->success('收货地址添加成功', url('index').$url, ['last_id'=>$last_id, "address_last"=>$this->addressMdl->find($last_id)]);
            }else{
              $this->error('添加失败');
            }
          }
        }else{
          $this->error('访问方式错误');
        }
    }
    /**
     * 编辑页面
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {

      if($id){
        $address_res = $this->addressMdl->find($id);
        return $this->fetch('edit', ['address'=>$address_res->toArray()]);
      }else{
        $this->error('没有这样的地址');
      }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id = 0)
    {
        if(!$id){
            $this->error('没有这样的地址');
        }
        if($this->request->isPost()){
          $data = $this->request->post();
          if(!$data['id']){
            $this->error('没有这样的地址');
          }else{
             $data = $this->request->post();
             $data['uid'] = isset($data['uid']) ? $data['uid'] : Session::get('qt_userId');
             //dump($data);exit;
             $validate_result = $this->validate($data, 'Address');
             if(!$validate_result){
                $this->error($validate_result);
             }else{
                if($this->addressMdl->isUpdate(true)->save($data)){
                    $this->success('保存成功', url('index'),['last_id'=>$this->addressMdl->id]);
                }else{
                    $this->error('保存失败');
                }
             }
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
     public function delete($id = 0, $ids = [])
     {
       $id = $id ? $id : $ids;
       if($id){
         $this->request->get();
         $user_address = Db::name('user')->where("address_now", $id)->find();
         if($user_address){
           Db::name('user')->where("id", $user_address['id'])->setField('address_now', '');
         }
         if($this->addressMdl->destroy($id)){
           $this->success('删除成功',url('index'));
         }else{
           $this->error('删除失败');
         }
       }else{
         $this->error('请选择需要删除的地址');
       }
     }
}
