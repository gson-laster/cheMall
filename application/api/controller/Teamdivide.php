<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/2 0002
 * Time: 下午 5:26
 */

namespace app\api\controller;


use app\common\model\User;
use think\Config;
use think\Controller;
use think\Db;
use think\cache\driver\Redis;
use libs\Data;

class Teamdivide extends Controller {
  protected static $Redis;
  protected static $company = null;
  protected function _initialize(){
    self::$Redis=new Redis(Config::get('redis'));
  }
  
  /**
   * 返回所有级别的代理集合
   */
  public function getAllAgent(){
    $result = [];
    // 1.所有代理
    $whereAnd['agent_type'] = ['<=', 3];
    $where['agent_type'] = ['<>', 0];
    $result = User::field('id,agent_type,pid')->where($where)->where($whereAnd)->select();
    
    return $result;
  }
  
  /**
   * 记录所有的团队成员
   * @author slide
   * @param $agentId
   * @return \think\response\Json
   *
   */
  public function getAllAgentTeamNote($agentId){
    $res = self::$Redis->get("agentTeamNote_".$agentId);
    // dump(json_decode($res['teams']));
    return json(json_decode($res['teams']));
  }
  
  /**
   * 静态获取所有已经完成会员
   * @methods
   * @desc
   * @author slide
   * @return mixed
   *
   */
  public static function getAllAgentComplete() {
    $res = Db::name('agent_achievements')->field('user_id')->where('is_complete', 1)->select();
    $result = array_column($res, 'user_id');
    
    return $result;
  }
  
  /**
   * 判断是否已经完成
   * @methods
   * @desc
   * @author slide
   * @param $ids
   *
   */
  public function getAgentIsComplete($id){
    $result = self::getAllAgentComplete();
    L('完成业绩的人'.var_export($result, true));
    return in_array($id, self::getAllAgentComplete());
  }
  
  /**
   * 收集代理团队
   * @author slide
   * @param $uid
   * @return array
   *
   */
  public function collectAgentTeam(){
    // 所有等级的代理
    $agent_result = $this->getAllAgent();
  
    self::$company = is_null(self::$company) ? User::where('is_employ_agent', 1)->find() : self::$company;
    
    // 2.代理往下4级
    foreach ($agent_result as $k => $v){
      if(self::$company->id == $v['id']) continue;
      L('用户'.$v['id'].'是否完成完成'.$this->getAgentIsComplete($v['id']));
      if($this->getAgentIsComplete($v['id'])) continue;  // 排除业绩已经满了的
      // call_user_func([$this, 'agentTeam'], $v['id']);
      // 查询队列
      $addFlage = queue::addQueue(Config::get('queue')['agentTeamNote'],'agentTeamPath', ['agent_id' => $v['id']]);
    }
    
    // 添加计算业绩
    foreach ($agent_result as $k => $v){
      if(self::$company->id == $v['id']) continue;
      L('用户'.$v['id'].'是否完成完成'.$this->getAgentIsComplete($v['id']));
      if($this->getAgentIsComplete($v['id'])) continue;  // 排除业绩已经满了的
      // call_user_func([$this, 'agentTeam'], $v['id']);
      queue::addQueue(Config::get('queue')['agentTeamFree'],'agentTeamPath',['agent_id' =>$v['id']]);
    }
    
  }
  
  /**
   * 清除redis
   * @methods
   * @desc
   * @author slide
   *
   */
  public function rm () {
    $redis = new Redis();
    for($i = 0; $i < 1400; $i ++) {
      $redis->rm('agentTeamNote_'.$i);
    }
  }
  
  /**
   * 代理团队数据
   * @methods
   * @desc
   * @author slide
   * @param $agent_id
   *
   */
  public function agentTeam($agent_id = 364){
    return json(getAgentChildUserListByAgentId($agent_id));exit;
  }
  
  /**
   * 计算团队业绩
   * @author slide
   * @param $team 团队路径
   * @return int
   *
   */
  public function computeTeamAchievement($agent_id, $start, $end){
    $total = 0;
    L('开始计算团队业绩'.$agent_id);
    // 取出所有的团队
    $agent_team = Db::name('team_note')->field('teams')->where('user_id', $agent_id)->select();
    
    L($agent_id.'团队结果'.var_export($agent_team, true));
    
    $agent_team = array_unique(array_column($agent_team, 'teams'));
    L('团队成员'.var_export($agent_team, true));
    $agent_team_ids = [];
    
    foreach ($agent_team as $k => $v){
      $temp = explode(',', $v);
      foreach ($temp as $key => $value) {
        if($value == '') continue;
        if(!in_array($value, $agent_team_ids)){
          array_push($agent_team_ids, $value);
        }
      }
    }
    
    array_push($agent_team_ids, $agent_id);
    $agent_team_ids = array_unique($agent_team_ids);
    
    // 计算团队业绩
    $agent_team_ids_str = implode(',', $agent_team_ids);
    L('参与计算的团队成员'.$agent_team_ids_str);
  
    if($agent_team_ids_str == '') {
      return $total;
    }
    
    $where['createtime'] = ['between', [$start, $end]];
    $where['user_id'] = ['IN', $agent_team_ids_str];
    
    $total = Db::name('divide_note')->where($where)->sum('total_money');
    
    L('团队业绩执行sql:'.Db::name('divide_note')->getLastSql());
    
    L('代理'.$agent_id.'团队业绩计算完毕'.$total);
    
    return is_null($total) ? 0 : $total;
  }
  
  /**
   * 计算个人业绩
   * @author slide
   * @param $uid
   * @return int
   *
   */
  public function computeSelfAchievement($uid, $start, $end) {
    $total = 0;
    L('开始计算个人业绩');
    // 获取当前时间段个人收入
    /*$where['ap.createtime'] = ['between', [$start, $end]];
    $where['pid'] = $uid;
    $where['ap.agent_type'] = ['IN','1,2,3'];
    $where['ap.status'] = 1;
    $res = (new User())->alias('u')->field(['u.id', 'u.agent_type as u_agent_type', 'ap.money'])->join("__AGENT_APPLY__ ap", 'ap.user_id=u.id')->where($where)->select();
    
    foreach ($res as $k => $v){
      $total += $v['money'];
    }*/
    
    $where['createtime'] = ['between', [$start, $end]];
    $where['user_id'] = $uid;
    $total = Db::name('divide_note')->where($where)->sum('total_money');
    
    L('个人业绩计算完毕');
    return is_null($total) ? 0 : $total;
  }
  
  /**
   * 是否完成业绩
   * @author slide
   * @param $uid
   * @return bool
   *
   */
  public function isComplete($uid, $self_ach, $team_ach, $achevement_flag = 0){
    $flag = false;
      // Db::name('leader_config')->
    L('团队业绩'.$team_ach);
    L('个人业绩'.$self_ach);
    switch ($achevement_flag){
      case 1:
        $leader_config = Db::name('leader_config')->where('id', 2)->find();
        $type = 2;
        break;
      case 2:
        $leader_config = Db::name('leader_config')->where('id', 3)->find();
        $type = 3;
        break;
      default :
        $leader_config = Db::name('leader_config')->where('id', 1)->find();
        $type = 1;
    }
    
    // 当当前用户业绩大于等于预定业绩时触发奖励机制
    if($self_ach >= $leader_config['self_achievement'] && $team_ach >= $leader_config['team_achievement']) {
      $flag = true;
      
      // 记录代理需要分成信息
      if($type == 1){
        $db_data = [
          'divided' => 0,
          'user_id' => $uid,
          'type'    => $type,
          'updatetime' => time(),
          'createtime' => time()
        ];
        Db::name('agent_need_divided')->insert($db_data);
      }else{
        $db_data = [
          'type' => $type,
          'updatetime' => time()
        ];
        Db::name('agent_need_divided')->where('user_id', $uid)->update($db_data);
      }
      // 分成
      queue::addQueue(Config::get('queue')['agentTeamDivide'],'agentTeamPath',['agent_id' =>$uid]);
    }
    L('记录分成');
    return $flag;
  }
  
  /**
   * 记录分成
   * @author slide
   * @param $data
   *
   */
  public function recordDivideNote($uid){
    // 1、确定用户分成比例
    $need_divided = Db::name('agent_need_divided')->where('user_id', $uid)->find();
    if(!$need_divided) return;
    
    $real_total_free = $this->countSelfRward($need_divided['type']);
    
    // 3、分成并记录
    $divide_data = [
      'user_id' => $uid,
      'order_id' => 0,
      'total_money' => $real_total_free,
      'type' => 3,
      'createtime' => time()
    ];
    Db::startTrans();
    try{
      Db::name('divide_note')->insert($divide_data);
      accountLog($uid, $real_total_free, 1, date('Y年m月', time()).'领导人奖励到账啦');
      Db::commit();
      return true;
    }catch (\Exception $e){
      Db::rollback();
      L('领导人'.$uid.'奖励发放失败');
      L('team error'.$e);
      return false;
    }
  }
  
  /**
   * 根据当前等级计算奖金
   * @author slide
   * @param $type
   * @param $count
   *
   */
  public function countSelfRward($type){
    //统计当前等级的人数
    $all_need_divide = Db::name('agent_need_divided')->field('id')->select();
    L('当前等级'.$type);
    // 公司利润
    $date = thisMonth();
    $company_profit = (new ComputeFree())->countProfit($date['start'], $date['end']);
    
    // 根据等级重新组合数据
    $need_agent = [];
    foreach ($all_need_divide as $k => $v){
      $need_agent[$v['type']][] = $v;
    }
    L('组合完的数据'. json_encode($need_agent, true));
    $total_money = 0;
    $point_one = Db::name('leader_config')->where('id', 1)->value('point');
    
    // 第一级用户只享受第一级分成
    // 第二级用户享受第一级和第二级分成
    // 第三级用户享受第一级第二级第三级分成
    if($type == 1) { // 全部享受
      $total_money = $company_profit * $point_one / count($all_need_divide);
    } else if($type == 2) { // 第二级和第三级享受
      $point_two = Db::name('leader_config')->where('id', 2)->value('point');
      $total_money = $company_profit * $point_one / count($all_need_divide) + ($company_profit * $point_two / (count($need_agent[2]) + count($need_agent[3])));
    } else if($type == 3) { // 第三级单独享受
      $point_two = Db::name('leader_config')->where('id', 2)->value('point');
      $point_three = Db::name('leader_config')->where('id', 3)->value('point');
      $total_money = $company_profit * $point_one / count($all_need_divide)  // 享受第一级
                    + ($company_profit * $point_two / (count($need_agent[2]) + count($need_agent[3])))  // 第二级
                    + ($company_profit * $point_three / count($need_agent[3])); // 享受第三级
    }
    
    return $total_money;
  }
  
  /**
   * 记录团队信息
   * @author slide
   * @param $data
   *
   */
  public function recordTeamNote($data){
    //$Redis=new Redis(Config::get('redis'));
    //self::$Redis->set("agentTeamNote_".$data['user_id'],json_encode($data, JSON_UNESCAPED_UNICODE));
  }
  
  /**
   * 记录业绩（团队和个人）
   * @author slide
   * @param $data
   *
   */
  public function recordAchievement($uid){
    $flag = false;
    // 查询是否是一个最高级别的leader 则不需要计算
    $result = Db::name('agent_need_divided')->field('type')->where('user_id', $uid)->find();
    L('计算'.$uid.'的业绩'.var_export($result, true));
    if($result && $result['type'] == 3) return;
    
    // 标识是否存在业绩记录
    $achievement_flag = $result ? $result['type'] : 0;
    
    // 判断有没存在
    $res = Db::name('agent_achievements')->where('user_id', $uid)->find();
    L('团队业绩结果'.var_export($res, true));
    
    $date = thisMonth();
    
    // self
    $self_achiement = $this->computeSelfAchievement($uid, $date['start'], $date['end']);
    L('个人业绩计算结果'.$self_achiement);
    
    // team
    $team_achiement = $this->computeTeamAchievement($uid, $date['start'], $date['end']);
    L('团队业绩计算结果'.$team_achiement);
  
    if($res){
      // 存在做加，并更新是否完成团队记录
      $self_data = ($self_achiement + $res['current_self_achievements']);
      $team_data = ($team_achiement + $res['current_team_achievements']);
      
      $real_self = is_null($self_data) ? 0 : $self_data;
      $real_team = is_null($team_data) ? 0 : $team_data;
      L('1111111111111111');
  
      $data = [
        'current_self_achievements' => $real_self,
        'current_team_achievements' => $real_team,
        'is_complete' => $this->isComplete($uid, $real_self, $real_team, $achievement_flag),
        'updatetime' => time()
      ];
  
      Db::name('agent_achievements')->where('user_id', $uid)->update($data);
      L('更新数据');
      $flag = true;
    }else{
      // 不存在添加
      $real_self = is_null($self_achiement) ? 0 : $self_achiement;
      $real_team = is_null($team_achiement) ? 0 : $team_achiement;
      L('111111');
      $data = [
        'user_id' => $uid,
        'current_self_achievements' => $real_self,
        'current_team_achievements' => $real_team,
        'is_complete' => $this->isComplete($uid, $real_self, $real_team, $achievement_flag),
        'updatetime' => time(),
        'createtime' => time()
      ];
  
      Db::name('agent_achievements')->insert($data);
      $flag = true;
      L('插入数据');
    }
    L('fanhui'.var_export($flag, true));
    return $flag;
  }
}
