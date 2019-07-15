<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/7 0007
 * Time: 上午 9:25
 */

namespace app\api\controller;


use app\common\model\User;
use think\Config;
use think\Db;

class Agentteamfivepoint {
  protected static $date = NULL;
  public function __construct() {
    self::$date = is_null(self::$date) ? thisMonth() : self::$date;
  }
  
  /**
   * 统计下级所有会员
   * @author slide
   * @param $uid
   */
  public function getChildren($uid, $where = []){
    $user_res = User::field('id,agent_type')->where('pid', $uid)->where($where)->select();
    
    return $user_res;
  }
  
  /**
   * 获取所有的代理
   * @author slide
   * @param string $agent_type
   *
   */
  public function getAllAgent($agent_type = '') {
    // 可以单独分成某个等级
    if($agent_type && $agent_type != ''){
      $where['agent_type'] = $agent_type;
    }else{
      $agent_need = Db::name('config')->where('id', 1)->value('fivepoint');
      $where['agent_type'] = $agent_need === 0 ? 1 : ['IN', '1,2,3'];
    }
    $company_account = Db::name('user')->where('is_employ_agent', 1)->value('id');
    $where['id'] = ['<>', $company_account];
    $agent_res = User::field('id,pid,agent_type')->where($where)->select();
    
    return $agent_res;
  }
  
  /**
   * 统计个人团队奖励
   * @desc
   * @author slide
   */
  public function countSelfTeamAward($uid){
    $divide_note = 0;
    // 下级会员
    $agent_children = $this->getChildren($uid, ['agent_type' => ['IN', "1,2,3"]]);
    $agent_children_ids = array_keys(convert_arr_key($agent_children, 'id'));
    L('代理'.$uid.'下级代理会员'.var_export($agent_children_ids, true));
    if(empty($agent_children_ids)) return $divide_note;
    
    // 统计收入
    $where['user_id'] = ['IN', implode(',', $agent_children_ids)];
    
    $where['createtime'] = ['between', [self::$date['start'], self::$date['end']]];
    $divide_note = Db::name('divide_note')->where($where)->sum('total_money');
    L($uid.'_查询下级收入sql'.Db::name('divide_note')->getLastSql());
    L('下级代理收入'.$divide_note);
    return is_null($divide_note) ? 0 : $divide_note;
  }
  
  /**
   * 奖励发放
   * @author slide
   * @param $uid
   *
   */
  public function divide2Agent($uid){
    // 奖励
    $reward = $this->countSelfTeamAward($uid);
    $reward = is_null($reward) ? 0 : $reward;
    L('代理'.$uid.'直推总业绩'.$reward);
  
    // 团队奖励计算
    $agent_reword = $reward * cache_data('site_config')['fivepoint_value'];
    L('团队业绩'.$agent_reword);
    
    if($agent_reword == 0) return true;
    
    Db::startTrans();
    try{
      // 记录分成
      $divide_note = [
        'user_id' => $uid,
        'total_money' => $agent_reword,
        'order_id' => 0,
        'type' => 4,
        'createtime' => time(),
        'level' => 0
      ];
      
      Db::name('divide_note')->insert($divide_note);
      //分成
      accountLog($uid, $agent_reword, 1, date('Y年m月').'团队奖励发放成功');
      Db::commit();
      return true;
    }catch (\Exception $e) {
      L('团队分成错误'.$e);
      Db::rollback();
      return false;
    }
  }
  /**
   * 触发添加队列
   * @desc
   * @author slide
   *
   */
  public function blur2Queue($agent_type = ''){
    // 循环添加计算任务
    $agent = $this->getAllAgent($agent_type);
    
    foreach ($agent as $k => $v){
      queue::addQueue(Config::get('queue')['agentTeamReward'],'agentTeamReward', ['uid' => $v['id']]);
    }
    return json(['status' => 'success']);
  }
}
