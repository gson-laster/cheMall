<?php
namespace app\common\model;
use think\Model;
/**
 *
 */
class SpecItem extends Model
{

  /**
   * 获取 tp_spec_item表 指定规格id的 规格项
   * @param int $spec_id 规格id
   * @return array 返回数组
   */
  public function getSpecItem($spec_id)
  {
      $arr = $this->where("spec_id = $spec_id")->order('id')->select();
      $arr = get_id_val($arr, 'id','item');
      return $arr;
  }
}

 ?>
