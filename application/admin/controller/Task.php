<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/26 0026
 * Time: 上午 10:47
 */

namespace app\admin\controller;
use think\Db;
/**
 * 定时任务执行记录
 * Class Task
 *
 * @package app\admin\controller
 */
class Task extends AdminBase {
  
  /**
   * 每天
   * @author: slide
   * @param int $page
   * @return mixed
   */
  public function everyDayaNote($page = 1){
    $res = Db::name('task_note')->where('type', 1)->paginate(\think\Config::get('pageSize'), false, ['page'=>$page]);
    
    return $this->fetch('ervey-day-list', ['list'=>$res]);
  }
  
  /**
   * 每月
   * @author: slide
   * @param int $page
   * @return mixed
   */
  public function everyMonthNote($page = 1){
    $res = Db::name('task_note')->where('type', 2)->paginate(\think\Config::get('pageSize'), false, ['page'=>$page]);
    return $this->fetch('ervey-month-list', ['list'=>$res]);
  }
}
