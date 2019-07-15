<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5 0005
 * Time: 上午 9:20
 */

namespace app\admin\controller;

use think\Db;

class Leaderconfig extends AdminBase {
  
  /**
   * 列表
   * @author slide
   * @return mixed
   */
  public function index () {
    $result = Db::name('leader_config')->select();
    
    return $this->fetch('index', ['list' => $result]);
  }
  
  /**
   * 获取单个数据
   * @author slide
   * @param string $id
   * @return \think\response\Json
   *
   */
  public function getConfigById($id = ''){
    if(!$id) return $this->ajax(4000, '参数错误');
    $res = Db::name('leader_config')->where('id', $id)->find();
    
    return $this->ajax(2000, '成功', '', $res);
  }
  
  /**
   * 添加更新
   * @author slide
   * @return \think\response\Json
   *
   */
  public function save(){
    if($this->request->isPost()){
      $data = $this->request->post();
      
      $type = input('id') > 0 ? 2 : 1;
      
      if($type == 1) {
        $res = Db::name('leader_config')->insert($data);
      }else{
        $res = Db::name('leader_config')->where('id', $data['id'])->update($data);
      }
      if($res){
        return $this->ajax(2000, 'success');
      }else {
        return $this->ajax(4000, 'error');
      }
    }else{
      return $this->ajax(4000, '访问方式错误');
    }
  }
  
}
