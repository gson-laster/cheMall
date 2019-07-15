<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/29 0029
 * Time: 下午 3:16
 */

namespace app\home\controller;

use  app\common\model\ServiceApply as serviceapplyModel;
use think\Db;
use think\Session;

class ServiceApply extends HomeBase
{

    protected $serviceapplyMdl;

    protected function _initialize()
    {
        parent::_initialize();
        $this->serviceapplyMdl = new serviceapplyModel();
    }

    /**
     * 申请成为服务商
     * @return array
     */
    public function index($keyword= '', $page = 1){
      $where = [];
      if($keyword) $where['title'] = ['like', "%{$keyword}%"];

      $list = model('service_center')->where($where)->order('id asc')->paginate(12, ['page' => $page]);
     if($this->request->isAjax()){
       return $this->ajax(2000, '查询成功', '', $list);
     }else{
       return $this->fetch('apply_service_center', ['list' => $list]);
     }
    }

    /**
     * 申请服务中心
     * @return array
     */
    public function addApply()
    {
        $data = input();
        $pid_sql = "select  count(*) from  ty_user  where agent_type<=3 and pid=" .Session::get('qt_userId');
        $result_pid = Db::execute($pid_sql);
        if ($result_pid > 30) {
            try {
                $data['num_vip']=$result_pid;
                $data['userid']=Session::get('qt_userId');
                Db::transaction();
                Db::name('serviceapply')->insert($data);
                Db::commit();
                if ($this->request->isAjax()) {
                    return $json = [
                        'code' => 200,
                        'data' => '申请成功！'
                    ];
                } else {
                    $this->success("申请成功！");
                }

            } catch (\Exception $e) {
                L('申请失败' . $e);
                Db::rollback();
                $this->error("申请失败");
            }
        } else {

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 400,
                    'data' => '申请失败,您不符合条件！'
                ];
            } else {
                $this->error("申请失败，您不符合条件！");
            }
        }


    }

}
