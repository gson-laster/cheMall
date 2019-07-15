<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/13 0013
 * Time: 下午 4:37
 */

namespace app\admin\controller;


use think\Db;
use think\Config;

class Transferaccount extends AdminBase {
  
  
  /**
   * 列表
   * @author: slide
   *
   * @param     $keyword phone|id|nickname
   * @param     $start_time
   * @param     $end_time
   * @param int $page
   *
   */
  public function index($keyword='', $start_time='', $end_time='', $page=1){
    $map = [];
    if($start_time){
      $map['createtime'] = ['between', [strtotime($start_time), time()]];
    }
    if($end_time){
      $map['createtime'] = ['between',[strtotime($end_time),strtotime($end_time)+86400000]];
    }
    if($start_time && $end_time){
      $map['createtime'] = ['between', [strtotime($start_time), strtotime($end_time)]];
    }
    if($keyword){
      $map['to_user_phone|to_user_id|to_user_nickname|from_user_phone|from_user_id|from_user_nickname'] = ['LIKE', "%{$keyword}%"];
    }
    
    // 列表
    $list = Db::name('transfer_accounts')->where($map)->order('createtime desc')->paginate(Config::get('pageSize'), false, ['page'=>$page]);
    //dump(Db::name('transfer_accounts')->getLastSql());
    return $this->fetch('index', ['list'=>$list]);
  }
}
