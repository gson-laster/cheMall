<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/29 0029
 * Time: 下午 4:03
 */

namespace app\admin\controller;

use  app\common\model\ServiceApply as serviceapplyModel;
use think\Exception;
use  think\Db;
use  think\Config;

class ServiceApply extends AdminBase
{
    protected $serviceapplyMdl;

    protected function _initialize()
    {
        parent::_initialize();
        $this->serviceapplyMdl = new serviceapplyModel();
    }

    /**
     * 获取申请列表
     * @param int $page
     * @return array
     */
    function   getserviceapplyList($page=1){
           $data=input();
            $where=[];
       if(isset($data['user_id'])){
           $where['a.user_id']=$data['user_id'];
       }
        $result_service=$this->serviceapplyMdl->alias('a')->field([
           'a.*','b.id as pid','b.nickname','b.phone','c.title','c.address'

       ])->join('__USER__ b', 'a.userid=b.id','left')->join('__SERVICE_CENTER__ c','a.service_center_id=c.id','left')->where($where)->order('a.id desc')->paginate(Config::get('pageSize'), false, [
           'page' => $page
       ]);
        if($result_service){
           if ($this->request->isAjax()) {
               return $json = [
                   'code' => 200,
                   'data' => $result_service
               ];
           } else {
               $this->assign("serviceinfo",$result_service);
              return $this->fetch();
           }
       }else{

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 400,
                    'data' => $result_service
                ];
            } else {
              $this->assign("serviceinfo",$result_service);
              return $this->fetch();
            }
       }
    }

    /**
     * 获取单条信息
     * @param string $id
     * @return array
     */
    function getServiceApplyone($id=''){
        $result_Service=$this->serviceapplyMdl->find($id);
        if($result_Service){

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 200,
                    'data' => $result_Service
                ];
            } else {
                $this->assign("serviceinfo",$result_Service);
                $this->fetch();
            }

        }else {

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 400,
                    'data' => $result_Service
                ];
            } else {
                $this->assign("serviceinfo", $result_Service);
                $this->fetch();
            }


        }}

    /**
     * 审核
     * @param string $id
     * @param string $isok
     * @param int $page
     * @return array
     */
       function  exminservicecentens($id='',$isok='',$page=1, $phone="", $address="",$title=""){
           $result_service=$this->serviceapplyMdl->alias('a')->field([
               'a.*','b.id as pid','b.nickname','b.phone'

           ])->join('__USER__ b', 'a.userid=b.id')->where("a.id",$id)->find();

           try {
              Db::startTrans();
               $this->serviceapplyMdl->where('id', $id)->update(["isok" => $isok, "isoktime" => time()]);
               $s = $isok == 1?2:0;
               Db::name('service_center')->where('id', $result_service['service_center_id'])->update(['phone' => $phone, 'address' => $address, 'title' => $title, 'status' => $s]);
               Db::commit();
    
                if ($this->request->isAjax()) {
                   return $json = [
                       'code' => 200,
                       'data' => "审核成功"
                   ];
               } else {
                   $this->fetch();
               }
           }catch ( Exception $e){

               L("审核失败！".$e);
               Db::rollback();
               if ($this->request->isAjax()) {
                   return $json = [
                       'code' => 400,
                       'data' => "审核失败"
                   ];
               } else {
                   $this->fetch();
               }
           }
       }





}
