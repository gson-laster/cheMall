<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/24 0024
 * Time: 下午 3:57
 */

namespace app\test\controller;


use think\Controller;

class Test extends Controller {
  
  public function index(){
    
    return $this->fetch();
  }
  
  public function callback(){
    file_put_contents(WEB_ROOT.'callback.txt', json_encode(input()));
  }
  
  public function test(){
  
  }
  
  
}
