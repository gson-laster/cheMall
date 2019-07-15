<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/7 0007
 * Time: 下午 3:53
 */

namespace app\home\controller;


use think\Config;
use think\Controller;
use think\Db;

class Inteldivided  extends Controller
{
    function  Inteldividedstart()
    {
        $data = Db::name('integral')->whereTime('endtime', '>', date("Y-m-d", intval(time())))->select();
        for ($i = 0; $i < count($data); $i++) {
              $thisdata=$data[$i];
            if (date("Y-m-d", intval(time())) == date('Y-m-d',  $thisdata['endtime']) &&  $thisdata [ 'integral'] > 0) {
                ex_balancechange( $thisdata['user_id'], "到期转结",  $thisdata['integral'], '+', 1);
                ex_intergral( $thisdata['id'],$thisdata['integral']);
            }else if (date("Y-m-d", intval(time())) < date('Y-m-d',  $thisdata['endtime']) &&  $thisdata['integral']> 0) {
                $change_num=$thisdata['integral']*Config::get('divisionratio_dayexchange');
                 ex_balancechange( $thisdata['user_id'], "积分转结日",  $change_num, '+', 1);
                 ex_intergral( $thisdata['id'],$change_num);

            }else {}


        }

    }


}