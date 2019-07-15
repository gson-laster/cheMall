<?php
namespace app\common\model;

use think\Model;
/**
 * 热搜
 */
class SearchHot extends Model
{

  protected $createTime = 'createtime';
  protected $insert = ['createtime'];

  //自定义初始化
  protected function initialize()
  {
      //需要调用`Model`的`initialize`方法
      parent::initialize();
  }

  /**
   * 创建时间
   * @return bool|string
   */
  protected function setCreatetimeAttr() {
      return time();
  }

  /**
   * 添加热搜词
   * @param    [type]                   $data [description]
   * @Author:  slade
   * @DateTime :2017-04-27T11:22:51+080
   */
  public function addHot($data){
    $res = $this->where("keywords", $data['keywords'])->find();
    if($res){
      $result = $this->where('id', $res['id'])->setInc('count', 1);
    }else{
      $result = $this->insert($data);
    }

    return $result;
  }

}

 ?>
