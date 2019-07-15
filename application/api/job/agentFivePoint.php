<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4 0004
 * Time: 上午 11:23
 */

namespace App\api\job;


use app\api\controller\Agentteamfivepoint;
use think\queue\Job;

class agentFivePoint {
  
  private static $teamHandel=null;
  
  public function queueAgentTeamReward(Job $job, $data) {
    print("<info>agent team reword job start"."</info>\n");
    $jobHandler = $this->_doTeamAgentReward($data);
    
    if($jobHandler) {
      $job->delete();
      print("<info>agent team reword job run success"."</info>\n");
  
    }else{
      print("<info>agent team reword job run faild"."</info>\n");
  
      if($job->attempts() > 3) {
        $job->delete();
        print("<info>agent team reword job delete success"."</info>\n");
      }
    }
  }
  
  /**
   * 分成计算
   * @author slide
   * @param $data
   * @return bool
   *
   */
  private function _doTeamAgentReward($data){
    return self::_handle()->divide2Agent($data['uid']);
  }
  
  public static function _handle(){
    return self::$teamHandel = is_null(self::$teamHandel) ? new Agentteamfivepoint() : self::$teamHandel;
  }
}
