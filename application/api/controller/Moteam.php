<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/18 0018
 * Time: 下午 4:45
 */

namespace app\api\controller;
use app\common\model\User;
use think\Config;
use think\Controller;
use think\Db;

class Moteam extends Controller {
  protected static $start = null;
  protected static $end = null;
  protected static $company_profit = null;
  protected static $company_account = 0;

  public function __construct () {
    self::$start = is_null(self::$start) ? thisMonth()['start'] : self::$start;
    self::$end = is_null(self::$end) ? thisMonth()['end'] : self::$end;
    self::$company_account = is_null(self::$company_account) ? Db::name('user')->where('is_employ_agent', 1)->value('id') : self::$company_account;
  }

  /**
   * 所有有分成权限的代理
   * @methods
   * @desc
   * @author slide
   *
   */
  public function getAllAgent() {
    $agent_user = (new User())->where('agent_type', '<>', 0)->select();

    return $agent_user;

  }

  /**
   * 个人直推
   * @methods
   * @desc
   * @author slide
   *
   */
  public function getSelfNumberOfNext($agent_id) {
    $where['a.updatetime'] = ['between', [self::$start, self::$end]];
    $where['u.pid'] = $agent_id;
    $self_next_agent = Db::name('agent_apply')->alias('a')->field("a.*, u.pid, u.is_cansharered")->join('__USER__ u', 'u.id=a.user_id')->where($where)->select();
    L('个人直推查询sql'.Db::name('agent_apply')->getLastSql());
    return $self_next_agent ? $self_next_agent : [];
  }

  /**
   * 团队推荐人数
   * @methods
   * @desc
   * @author slide
   *
   */
  public function getTeamNumberOfChild($agent_id){

    // 代理团队领导人
    $user_childs = getAgentChildUserListByAgentId($agent_id);
    L('teams path\n\r'.var_export($user_childs, true));
    $agent_child_ids = $user_childs;

    $tems = implode(',', array_keys($agent_child_ids));
    L('团队成员', $tems);

    // 判断数据库是否已经有了
    $where = ['user_id' => $agent_id, 'teams' => $tems];
    $teams_res = Db::name('team_note')->where($where)->find();
    L('执行sql'.Db::name('team_note')->getLastSql());
    L('结果'.var_export($teams_res, true));
    $res = false;

    if(!$teams_res){
      $teams_data = [
        "user_id" => $agent_id,
        "teams" => $tems,
        'is_full' => 0,
        'updatetime' => time(),
        'createtime' => time()
      ];
      L('执行到了这里.'.var_export($teams_data, true));
      $res = Db::name('team_note')->insert($teams_data);
    }
    L('执行到了这里啊啊');
    // 统计团队成员本月直推会员
    if($tems == '') return [];
    $tems .= ','. $agent_id;
    $wh['u.pid'] = ['IN', $tems];
    $wh['a.createtime'] = ['between', [self::$start, self::$end]];
    $team_agent_number_child = Db::name('agent_apply')->alias('a')->join('__USER__ u', 'a.user_id=u.id', 'left')->where($wh)->select();
    L('执行到了这里啊啊啊啊啊啊啊啊啊啊');
    L('执行到了查询sql'.Db::name('agent_apply')->getLastSql());
    return $team_agent_number_child ? $team_agent_number_child : [];
  }

  /**
   * 计算是否完成了业绩
   * @methods
   * @desc
   * @author slide
   * @param $user_id
   *
   */
  public function getAgentIsComplete($user_id) {
    $res = Db::name('mo_agent_number')->where('user_id', $user_id)->find();

    if($res && $res['is_cansharered'] == 1) {
      return true;
    }else {
      return false;
    }
  }

  /**
   * 计算本月利润
   * @methods
   * @desc
   * @author slide
   *
   */
  public function countThisMonthProfit() {
    return (new ComputeFree())->countProfit(self::$start, self::$end);
  }

  /**
   * 给代理分成
   * @methods
   * @desc
   * @author slide
   *
   */
  public function dividedToAgent($uid){
    L('当前用户'.$uid);
    L($uid.'是否完成业绩'. $this->getAgentIsComplete($uid));

    if(self::$company_account == $uid) return true;

    // 判断当前用户是否完成业绩
    if($this->getAgentIsComplete($uid)){
      // 分成任务
      // 公司利润
      self::$company_profit = self::$company_profit ? self::$company_profit : $this->countThisMonthProfit();

      L('当月公司利润'.self::$company_profit);

      // 获取分成信息
      $user_share_red_info = Db::name('mo_agent')->where('user_id', $uid)->find();
      $user_share_red_all_info = Db::name('mo_agent')->select();

      $user_share_red_result = [];
      $mo_agent_num = [
        '1' => 0,
        '2' => 0,
        '3' => 0
      ];
      foreach ($user_share_red_all_info as $k => $v) {
        if(!isset($user_share_red_result[$v['type']])) {
          $user_share_red_result[$v['type']] = [];
        }
        $user_share_red_result[$v['type']][] = $v;
        $mo_agent_num[$v['type']] ++;
      }

      // 计算分成
      //$need_divide_money = $user_share_red_info['commission'] * self::$company_profit;
      $mo_agent_config = Config::get('mo_agent_config');
      $divide_total = 0;

      switch ($user_share_red_info['type']){
        case 1:
          $divide_total = (self::$company_profit * $mo_agent_config[1]['commission']) / ($mo_agent_num[1] + $mo_agent_num[2] + $mo_agent_num[3]);
        break;
        case 2:
          $divide_total =
            ((self::$company_profit * $mo_agent_config[1]['commission']) / ($mo_agent_num[1] + $mo_agent_num[2] + $mo_agent_num[3]))
            + (self::$company_profit * $mo_agent_config[2]['commission'] / ($mo_agent_num[2] + $mo_agent_num[3]));
        break;
        case 3:
          $divide_total =
            ((self::$company_profit * $mo_agent_config[1]['commission']) / ($mo_agent_num[1] + $mo_agent_num[2] + $mo_agent_num[3]))
            + (self::$company_profit * $mo_agent_config[2]['commission'] / ($mo_agent_num[2] + $mo_agent_num[3])) +
            (self::$company_profit * $mo_agent_config[3]['commission'] / ($mo_agent_num[3]));
        break;
      }

      divideNote([
        'user_id' => $uid,
        'order_id' => 0,
        'commission' => 0,
        'total_money' => $divide_total,
        'level' => 0,
        'type' => 2
      ]);
      L('当前用户能分到的金额'.$divide_total);
      accountLog($uid, $divide_total, 1, '完成业绩获得当月分红补贴');

      return true;
    }else{
      // 计算业绩
      $this->countAchementToAgent($uid);

      return true;
    }
  }

  /**
   * 判断是否具有分成资格
   * @methods
   * @desc
   * @author slide
   *
   * @param array $self
   * @param array $team
   * @param       $type
   *
   * @return bool
   *
   */
  public function isCanShareRed($self = [], $team = [], $type){
    $config = Config::get('mo_agent_config')[$type];

    if($self[0] + $self[1] >= $config['selfNumber'] && $team[0] + $team[1] >= $config['teamNumber']){
      return true;
    }else{
      return false;
    }
  }

  /**
   * 计算当前会员业绩
   * @methods
   * @desc
   * @author slide
   * @param $uid
   *
   */
  public function countAchementToAgent($uid){
    // 计算代理个人业绩
    $selfNumberOfNext = $this->getSelfNumberOfNext($uid);
    L($uid. '直推'. var_export($selfNumberOfNext, true));

    // 计算团队业绩
    $teamNumberOfChild = $this->getTeamNumberOfChild($uid);
    L($uid. '团队'. var_export($teamNumberOfChild, true));


    // 更新团队信息（完成则立即加入分成）
    $res = Db::name('mo_agent_number')->where('user_id', $uid)->find();

    // 检测当前用户的身份
    $mo_agent_res = Db::name('mo_agent')->where('user_id', $uid)->find();
    $agent_type = $mo_agent_res ? $mo_agent_res['type'] : 1;

    if($res) {
      // 更新
      $is_cansharered = $this->isCanShareRed([$res['self_number_next'], count($selfNumberOfNext)], [$res['team_number_child'], count($teamNumberOfChild)], $agent_type);
      $data = [
        'self_number_next' => count($selfNumberOfNext) + $res['self_number_next'],
        'team_number_child' => count($teamNumberOfChild) + $res['team_number_child'],
        'is_cansharered' => $is_cansharered,
        'updatetime' => time()
      ];
      Db::name('mo_agent_number')->where('user_id', $uid)->update($data);
    }else{
      // 创建
      $is_cansharered = $this->isCanShareRed([0, count($selfNumberOfNext)], [0, count($teamNumberOfChild)], $agent_type);
      $data = [
        'user_id' => $uid,
        'self_number_next' => count($selfNumberOfNext),
        'team_number_child' => count($teamNumberOfChild),
        'is_cansharered' => $is_cansharered,
        'createtime' => time(),
        'updatetime' => time()
      ];
      Db::name('mo_agent_number')->insert($data);
    }

    if($is_cansharered) {
      $mo_agent_config = Config::get('mo_agent_config')[$agent_type];
      if($mo_agent_res){
        $data = [
          'type' => $agent_type,
          'commission' => $mo_agent_config['commission'],
          'createtime' => time()
        ];
        Db::name('mo_agent')->where('user_id', $uid)->update($data);
      }else{
        $data = [
          'user_id' => $uid,
          'type' => $agent_type,
          'commission' => $mo_agent_config['commission'],
          'createtime' => time()
        ];
        Db::name('mo_agent')->insert($data);
      }
      $this->dividedToAgent($uid);
    }

  }

  /**
   * 创建计算任务
   * @methods
   * @desc
   * @author slide
   *
   */
  public function createCountJob(){
    $agent_user = $this->getAllAgent();
    foreach ($agent_user as $v) {
      call_user_func_array(['\\app\\api\\controller\\queue', 'addQueue'], [Config::get('queue')['moAgentTeamDivide'],'moAgentTeamPath',['user_agent_id' => $v['id']]]);
      //$this->createCountJob($v['id']);
    }
  }
}
