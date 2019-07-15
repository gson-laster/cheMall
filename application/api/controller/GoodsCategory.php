<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\common\model\GoodsCategory as goodsMdl;

class GoodsCategory extends Controller
{
  protected $cateMdl;
  protected function _initialize(){
    parent::_initialize();
    $this->cateMdl = new goodsMdl();
  }
  public function get_category(){
      $parent_id = input('get.parent_id/d'); // 商品分类 父id
      $list = $this->cateMdl->where("pid", $parent_id)->select();
      $html = '';
      foreach($list as $k => $v)
          $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
      exit($html);
  }
}
