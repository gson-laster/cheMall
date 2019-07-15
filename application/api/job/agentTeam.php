<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4 0004
 * Time: 上午 11:24
 */

namespace App\api\job;

use app\api\controller\Teamdivide;
use think\cache\driver\Redis;
use think\Exception;
use think\queue\Job;
use think\Db;

class agentTeam {
  private static $teamDivide = null;
  /**
   * 任务一 记录团队数据
   * @param Job            $job      当前的任务对象
   * @param array|mixed    $data     发布任务时自定义的数据
   */
  public function queueAgentTeamNote(Job $job,$data){
    print("<info>path task start!"."</info>\n");
  
    $isJobDone = $this->doAgentTeamNoteJob($data);
    print("<info>{$isJobDone}"."</info>\n\r");
    if ($isJobDone) {
      // 触发新的添加人任务
      //如果任务执行成功， 记得删除任务
      $job->delete();
      print("<info>task success!"."</info>\n");
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
  
  /**
   * 任务二 计算业绩
   * @param Job            $job      当前的任务对象
   * @param array|mixed    $data     发布任务时自定义的数据
   */
  public function queueAgentTeamFreeJob(Job $job,$data){
    print("<info>team free task start!"."</info>\n");
    $isJobDone = $this->_doAgentTeamFreeJob($data);
    L('team free task run'. json_decode($isJobDone));
    if ($isJobDone) {
      // 触发新的添加人任务
      //如果任务执行成功， 记得删除任务
      $job->delete();
      print("<info>team free task success!"."</info>\n");
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
  
  /**
   * 任务三 进行分成
   * @param Job            $job      当前的任务对象
   * @param array|mixed    $data     发布任务时自定义的数据
   */
  public function queueAgentDivideJob(Job $job,$data){
    print("<info>divide task start!"."</info>\n");
  
    $isJobDone = $this->_doAgentDivideFreeJob($data);
    
    if ($isJobDone) {
      // 触发新的添加人任务
      //如果任务执行成功， 记得删除任务
      $job->delete();
      print("<info>divide task success!"."</info>\n");
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
  /**
   * 失败重新发布
   * @author slide
   * @param \think\queue\Job $job
   * @param                  $data
   */
  /*public function failed(Job $job, $data){
    $job->delete();
    print('delete success');
  }*/
  /**
   * 团队数据从redis入mysql库
   * @param array|mixed    $data     发布任务时自定义的数据
   * @return boolean                 任务执行的结果
   */
  private function doAgentTeamNoteJob($data) {
      // 数据
      // die('here');
      $agent_id = $data['agent_id'];
      
      // 代理团队领导人
      $user_childs = getAgentChildUserListByAgentId($agent_id);
      L('teams path\n\r'.var_export($user_childs, true));
      $agent_child_ids = $user_childs;
      
      $tems = implode(',', array_keys($agent_child_ids));
      L('团队成员', $tems);
      
      // 判断数据库是否已经有了
      $where = ['user_id' => $agent_id, 'teams' => $tems];
      $teams_res = Db::name('team_note')->where($where)->find();
  
      $res = false;
      
      if(!$teams_res){
        $teams_data = [
          "user_id" => $agent_id,
          "teams" => $tems,
          'is_full' => 0,
          'updatetime' => time(),
          'createtime' => time()
        ];
        $res = Db::name('team_note')->insert($teams_data);
      }
      // $res = $res != false ? true : false;
      return $res ? true : false;
  }
  
  /**
   * 计算业绩
   * @author slide
   * @param $uid
   *
   */
  private function _doAgentTeamFreeJob($uid) {
    L('开始调用业绩计算');
    $result = self::teamDivideHandle()->recordAchievement($uid['agent_id']);
    L('计算结果返回'.json_encode($result));
    return $result;
  }
  
  /**
   * 发放奖励
   * @author slide
   * @param $uid
   *
   */
  private function _doAgentDivideFreeJob($uid){
    $result = self::teamDivideHandle()->recordDivideNote($uid['agent_id']);
    return $result;
  }
  
  /**
   * 返回实例
   * @author slide
   * @return \app\api\controller\Teamdivide|null
   */
  protected static  function teamDivideHandle(){
    self::$teamDivide = is_null(self::$teamDivide) ? new Teamdivide () : self::$teamDivide;
    
    return self::$teamDivide;
  }
}
