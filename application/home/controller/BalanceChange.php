<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/1 0001
 * Time: 上午 10:52
 */

namespace app\home\controller;


use think\Db;
use  think\Config;
use think\Session;


class BalanceChange extends  HomeBase
{

    /**
     * 积分中心  记录查询
     *
     * type=1  初次跳转
     * @param int $page
     * @return mixed
     */
    public function   toIntegralCenter($page=1){
       $where=[];

        if(input('type')==1){
            $where['userid']=Session::get("qt_userId");
            $where['change_type']=1;
            $user_res= Db::mame('user')->where('id', Session::get('qt_userId'))->value('record');
            $result_integral=Db::name('balance_change')->whereTime('updatetime', 'last month')->where($where)->order('id desc')->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);
            $this->assign("record",$user_res);
            $this->assign("intergral",$result_integral);
            return $this->fetch();
        }else{
            $where['userid']=Session::get("qt_userId");
            $where['change_type']=1;
            if (isset($data['updatetime'])) {
                $date = to_sex_month(true);

                foreach ($date as $k => $v) {
                    if ($k == intval($data['updatetime'])) {
                        $where['updatetime'] = [
                            'between',
                            [
                                $v[0],
                                $v[1]
                            ]
                        ];
                    }

                }

            }

            $user_res= Db::name('user')->where('id', Session::get('qt_userId'))->value('record');
            $result_integral=Db::name('balance_change')->where($where)->order('id desc')->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);
            $this->assign("record",$user_res);
            $this->assign("intergral",$result_integral);
            return $this->fetch('user/point');



           }




     }


    /**
     * @param string $page
     * change_type  1.积分 2.赠品券 3.订货额
     *
     */
    public  function  getBalanceChangeList($page=1){
        $where=[];
    if(Session::get("qt_userId")){
        $where['userid']=Session::get("qt_userId");
    }
    if(input('change_type')){
        $where['change_type']=input('change_type');
    }
    $result_user_balance=  Db::name('balance_change')->alias('a')->field(['a.*','b.phone','b.id as bid'])->join('__USER__ b','a.userid=b.id')->where($where)->order('a.id desc')->paginate(Config::get('pageSize'), false, [
        'page' => $page
    ]);
        if($result_user_balance){
            if($this->request->isAjax()){
                return  $json=[
                    'code'=>200,
                    'data'=>$result_user_balance
                ];
            }else{
                $this->assign("data",$result_user_balance);
                return  $this->fetch();
            }
        }else{

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 400,
                    'data' => $result_user_balance
                ];
            } else {
                $this->assign("data", $result_user_balance);
                return $this->fetch();
            }
        }
    }

    /**
     * 获取积分详情
     * @param $id
     * @return array|mixed
     */
    public function   getbalanceChange($id){

        $result_user_rech= Db::name('balance_change')->alias('a')->field(['a.*','b.id as bid','b.phone','b.nickname'])->join('__USER__ b','a.userid=b.id','left')->find($id);
        if($result_user_rech){
            if($this->request->isAjax()){
                return  $json=[
                    'code'=>200,
                    'data'=>$result_user_rech

                ];
            }else{
                $this->assign("data",$result_user_rech);
                return  $this->fetch('service_center/getbalancechange');

            }
        }else{

            if ($this->request->isAjax()) {
                return $json = [
                    'code' => 400,
                    'data' => $result_user_rech
                ];
            } else {
                $this->assign("data", $result_user_rech);
                return $this->fetch('service_center/getbalancechange');
            }



        }




    }





}