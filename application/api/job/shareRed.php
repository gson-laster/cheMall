<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/2 0002
 * Time: 上午 8:59
 */

namespace App\api\job;
use app\api\controller\Sharered as ShareRedAct;
use think\queue\Job;
class shareRed {
  public  function  queueShareRed (Job $job,$data) {
    print("<info>ShareRed task start!"."</info>\n");

    $isJobDone = $this->doShareRed($data);
    print("<info>{$isJobDone}"."</info>\n\r");
    if ($isJobDone) {
      // 触发新的添加人任务
      //如果任务执行成功， 记得删除任务
      $job->delete();
      print("<info>ShareRed task success!"."</info>\n");
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

  public function doShareRed ($data) {
    $result = (new ShareRedAct())->computedShareRed($data['user_id'], $data['agent_type'], $data['money'], $data['total_share_money']);
    L('执行结果'.var_export($result));
    return $result;
  }
}
