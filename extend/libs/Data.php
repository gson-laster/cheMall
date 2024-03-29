<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/3 0003
 * Time: 下午 4:30
 */

namespace libs;

use app\api\controller\queue;
use think\cache\driver\Redis;
use think\Config;

class Data
{
  protected static $Redis;
  public function __construct () {
    // self::$Redis = new Redis(Config::get('redis'));
  }
  
  /**
   * 返回多层栏目
   * @param $data 操作的数组
   * @param int $pid 一级PID的值
   * @param string $html 栏目名称前缀
   * @param string $fieldPri 唯一键名，如果是表则是表的主键
   * @param string $fieldPid 父ID键名
   * @param int $level 不需要传参数（执行时调用）
   * @return array
   */
  public function channelLevel($data, $pid = 0, $html = "&nbsp;", $fieldPri = 'cid', $fieldPid = 'pid', $level = 1, $path='')
  {
    if (empty($data)) {
      return array();
    }
    $arr = array();
    foreach ($data as $v) {
      if ($v[$fieldPid] == $pid) {
        $arr[$v[$fieldPri]] = $v;
        $arr[$v[$fieldPri]]['_level'] = $level;
        $arr[$v[$fieldPri]]['path'] =$path;
        //dump($v[$fieldPri]);
        //dump($this->hasChild($data, $v[$fieldPri], $fieldPid));
        /*if($this->hasChild($data, $v[$fieldPri], $fieldPid) != true){
          if($level >= 5){
            queue::addQueue(Config::get('queue')['agentTeamNote'],'agentTeamPath', ['path' => $path, 'is_full' => 1,'level' => $level]);
          }else{
            queue::addQueue(Config::get('queue')['agentTeamNote'],'agentTeamPath',['path' => $path, 'is_full' => 0]);
          }
        }
        L('写入redissuccess');*/
        $arr[$v[$fieldPri]]["_data"] = $this->channelLevel($data, $v[$fieldPri], $html, $fieldPri, $fieldPid, $level + 1, $path."_".$v[$fieldPri]);
        // $arr[$v[$fieldPri]]['_html'] = str_repeat($html, $level - 1);
      }
    }
    return $arr;
  }
  
  /**
   * 获得所有子栏目
   * @param $data 栏目数据
   * @param int $pid 操作的栏目
   * @param string $html 栏目名前字符
   * @param string $fieldPri 表主键
   * @param string $fieldPid 父id
   * @param int $level 等级
   * @return array
   */
  public function channelList($data, $pid = 0, $html = "&nbsp;", $fieldPri = 'cid', $fieldPid = 'pid', $level = 1)
  {
    $data = $this->_channelList($data, $pid, $html, $fieldPri, $fieldPid, $level);
    if (empty($data))
      return $data;
    foreach ($data as $n => $m) {
      if ($m['_level'] == 1)
        continue;
      $data[$n]['_first'] = false;
      $data[$n]['_end'] = false;
      if (!isset($data[$n - 1]) || $data[$n - 1]['_level'] != $m['_level']) {
        $data[$n]['_first'] = true;
      }
      if (isset($data[$n + 1]) && $data[$n]['_level'] > $data[$n + 1]['_level']) {
        $data[$n]['_end'] = true;
      }
    }
    //更新key为栏目主键
    $category=array();
    foreach($data as $d){
      $category[$d[$fieldPri]]=$d;
    }
    return $category;
  }
  
  //只供channelList方法使用
  private function _channelList($data, $pid = 0, $html = "&nbsp;", $fieldPri = 'cid', $fieldPid = 'pid', $level = 1)
  {
    if (empty($data))
      return array();
    $arr = array();
    foreach ($data as $v) {
      $id = $v[$fieldPri];
      if ($v[$fieldPid] == $pid) {
        $v['_level'] = $level;
        $v['_html'] = str_repeat($html, $level - 1);
        array_push($arr, $v);
        $tmp = $this->_channelList($data, $id, $html, $fieldPri, $fieldPid, $level + 1);
        $arr = array_merge($arr, $tmp);
      }
    }
    return $arr;
  }
  
  /**
   * 获得树状数据
   * @param $data 数据
   * @param $title 字段名
   * @param string $fieldPri 主键id
   * @param string $fieldPid 父id
   * @return array
   */
  public function tree($data, $title, $fieldPri = 'cid', $fieldPid = 'pid', $val = 0)
  {
    if (!is_array($data) || empty($data))
      return array();
    $arr = Data::channelList($data, $val, '', $fieldPri, $fieldPid);
    foreach ($arr as $k => $v) {
      $str = "";
      if ($v['_level'] > 2) {
        for ($i = 1; $i < $v['_level'] - 1; $i++) {
          $str .= "│&nbsp;&nbsp;&nbsp;&nbsp;";
        }
      }
      if ($v['_level'] != 1) {
        $t = $title ? $v[$title] : '';
        if (isset($arr[$k + 1]) && $arr[$k + 1]['_level'] >= $arr[$k]['_level']) {
          $arr[$k]['_'.$title] = $str . "├─ " . $v['_html'] . $t;
        } else {
          $arr[$k]['_'.$title] = $str . "└─ " . $v['_html'] . $t;
        }
      } else {
        $arr[$k]['_'.$title] = $v[$title];
      }
    }
    //设置主键为$fieldPri
    $data = array();
    foreach ($arr as $d) {
      $data[$d[$fieldPri]] = $d;
    }
    return $data;
  }
  
  /**
   * 获得所有父级栏目
   * @param $data 栏目数据
   * @param $sid 子栏目
   * @param string $fieldPri 唯一键名，如果是表则是表的主键
   * @param string $fieldPid 父ID键名
   * @return array
   */
  public function parentChannel($data, $sid, $fieldPri = 'cid', $fieldPid = 'pid')
  {
    if (empty($data)) {
      return $data;
    } else {
      $arr = array();
      foreach ($data as $v) {
        if ($v[$fieldPri] == $sid) {
          $arr[] = $v;
          $_n = $this->parentChannel($data, $v[$fieldPid], $fieldPri, $fieldPid);
          if (!empty($_n)) {
            $arr = array_merge($arr, $_n);
          }
        }
      }
      return $arr;
    }
  }
  
  /**
   * 判断$s_cid是否是$d_cid的子栏目
   * @param $data 栏目数据
   * @param $sid 子栏目id
   * @param $pid 父栏目id
   * @param string $fieldPri 主键
   * @param string $fieldPid 父id字段
   * @return bool
   */
  function isChild($data, $sid, $pid, $fieldPri = 'cid', $fieldPid = 'pid')
  {
    $_data = $this->channelList($data, $pid, '', $fieldPri, $fieldPid);
    foreach ($_data as $c) {
      //目标栏目为源栏目的子栏目
      if ($c[$fieldPri] == $sid)
        return true;
    }
    return false;
  }
  
  /**
   * 检测是不否有子栏目
   * @param $data 栏目数据
   * @param $cid 要判断的栏目cid
   * @param string $fieldPid 父id表字段名
   * @return bool
   */
  function hasChild($data, $cid, $fieldPid = 'pid')
  {
    foreach ($data as $d) {
        if ($d[$fieldPid] == $cid) return true;
    }
    return false;
  }
  
  /**
   * 递归实现迪卡尔乘积
   * @param $arr 操作的数组
   * @param array $tmp
   * @return array
   */
  function descarte($arr, $tmp = array())
  {
    $n_arr = array();
    foreach (array_shift($arr) as $v) {
      $tmp[] = $v;
      if ($arr) {
        $this->descarte($arr, $tmp);
      } else {
        $n_arr[] = $tmp;
      }
      array_pop($tmp);
    }
    return $n_arr;
  }
  
}
