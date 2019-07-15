<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 下午 4:41
 */

namespace app\common\model;


use think\Db;

class ServiceCenter extends BaseModel {
  public function afterSaveHandle($id = '', $conent = '', $images_arr = []){
    if($conent && $conent != '')  {
      $detail = Db::name('service_center_detail')->where('service_id', $id)->find();
  
      if($detail) {
        Db::name('service_center_detail')->where('service_id', $id)->delete();
        
      }
      Db::name('service_center_detail')->insert([
          'service_id' => $id,
          'content' => $conent,
          'createtime' => time(),
          'updatetime' => time()
        ]);
    }
    if($images_arr && !empty($images_arr)){
      $images = Db::name('service_center_images')->where('service_id', $id)->find();
  
      if($images) {
        Db::name('service_center_images')->where('service_id', $id)->delete();
        
      }
      $imageData = [];
        foreach ($images_arr as $k => $v) {
          $tmp = [
            'service_id' => $id,
            'image_path' => $v,
            'createtime' => time()
          ];
          $imageData[] = $tmp;
        }
        Db::name('service_center_images')->insertAll($imageData);
    }
  }

}
