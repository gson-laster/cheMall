<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4 0004
 * Time: 上午 11:48
 */

namespace app\api\controller;

use think\Queue as QueueMdl;
class queue {
  /**
   * 添加任务队列
   * @author slide
   * @param $jobHandlerClassName
   * @param $jobQueueName
   * @param $data
   * @return bool
   *
   */
  public static function addQueue($jobHandlerClassName, $jobQueueName = null, $data = []){
    
    // 1.当前任务将由哪个类来负责处理。
    //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
    $jobHandlerClassName  = $jobHandlerClassName ? $jobHandlerClassName : 'app\test\job\Hello';
    
    // 2.当前任务归属的队列名称，如果为新队列，会自动创建
    $jobQueueName  	  = $jobQueueName;
    
    // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
    //   ( jobData 为对象时，需要在先在此处手动序列化，否则只存储其public属性的键值对)
    $jobData       	  = $data ;
    
    // 4.将该任务推送到消息队列，等待对应的消费者去执行
    $isPushed = QueueMdl::push( $jobHandlerClassName , $jobData , $jobQueueName );
    
    // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
    return $isPushed !== false ? true : false;
  }
  
}
