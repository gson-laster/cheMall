<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/18 0018
 * Time: 下午 7:47
 */

namespace App\api\job;

use app\api\controller\Moteam;
use think\queue\Job;
class moAgentTeam {
  
  
  public  function  queueMoAgentDivide (Job $job,$data) {
    print("<info>MoAgentDivide task start!"."</info>\n");
  
    $isJobDone = $this->doMoAgentDivideJob($data);
    print("<info>{$isJobDone}"."</info>\n\r");
    if ($isJobDone) {
      // 触发新的添加人任务
      //如果任务执行成功， 记得删除任务
      $job->delete();
      print("<info>MoAgentDivide task success!"."</info>\n");
    }else{
      if ($job->attempts() > 3) {
        //通过这个方法可以检查这个任务已经重试了几次了
        print("<warn>task run fail, deleting..."."</warn>\n");
        $job->delete();
        print("<info>deleted success."."</info>\n");
        // 也可以重新发布这个任务
        //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
        //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
      }
    }
  }
  
  protected function doMoAgentDivideJob ($data) {
    L('数据'.var_export($data, true));
    try{
      $result = (new Moteam())->dividedToAgent($data['user_agent_id']);
      
    }catch (\Exception $e) {
      L('错误'.$e);
    }
    L('执行结果'.$result);
    return $result;
  }
}
