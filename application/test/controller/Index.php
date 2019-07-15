<?php
namespace app\test\controller;

use app\common\model\Order;
use think\Config;
use think\Controller;
use GatewayClient\Gateway;
use libs\Imgcompress;
use think\Queue;

/**
 *
 */

class Index extends Controller
{

   public function index(){
     //$img = (new Imgcompress(WEB_ROOT.'/public/static/images/404_03.jpg', 1))->compressImg(WEB_ROOT.'/1.jpg');
     dump(strtotime('-1 day'));
	 //	return $this->fetch();
	 }
  
  public function actionWithHelloJob(){
    
    // 1.当前任务将由哪个类来负责处理。
    //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
    $jobHandlerClassName  = 'app\test\job\Hello';
    // 2.当前任务归属的队列名称，如果为新队列，会自动创建
    $jobQueueName  	  = "helloJobQueue";
    // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
    //   ( jobData 为对象时，需要在先在此处手动序列化，否则只存储其public属性的键值对)
    $jobData       	  = [ 'ts' => time(), 'bizId' => uniqid() , 'a' => 1 ] ;
    // 4.将该任务推送到消息队列，等待对应的消费者去执行
    $isPushed = Queue::push( $jobHandlerClassName , $jobData , $jobQueueName );
    // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
    if( $isPushed !== false ){
      echo date('Y-m-d H:i:s') . " a new Hello Job is Pushed to the MQ"."<br>";
    }else{
      echo 'Oops, something went wrong.';
    }
  }
  //
	// public function chart() {
	//     // if(empty($_SESSION['name'])) {
	//     //     $this->error('请登录！','/Chart/index');
  //     //   }else{
	//     //     $this->display();
  //     //   }
  //     return $this->fetch();
  //   }
  //
	// public function join() {
	//     $photo = input('get.photo','');
  //       $username = input('get.username','');
  //       // if(empty($photo) || empty($username)) {
  //       //     $this->ajaxReturn(array('sz'=>2,'ans'=>'未知错误！'));
  //       // }else{
  //       //
  //       // }
  //       $_SESSION['name'] = $username;
  //       return json(array('sz'=>1,'ans'=>'登陆成功！'));
  //   }
  //
  //   public function upload() {
  //       $upload = new \Think\Upload();// 实例化上传类
  //       $upload->rootPath  =      './Public/uploads/'; // 设置附件上传根目录
  //       // 上传单个文件
  //       $info   =   $upload->upload();
  //       if(!$info) {// 上传错误提示错误信息
  //           $this->error($upload->getError());
  //       }else{// 上传成功 获取上传文件信息
  //           foreach($info as $file){
  //               $url = './Public/uploads/'.$file['savepath'].$file['savename'];
  //               $image = new \Think\Image();
  //               $image->open($url);
  //               $image->thumb(200, 200,\Think\Image::IMAGE_THUMB_CENTER)->save($url);
  //               $url = trim($url,'.');
  //               //预留接口 ************
  //               //在这里可以把图片地址写入数据库 或者对图片进行操作 例如生成缩略图
  //
  //               //这里返回每一次的URL pulpload 规则 参见 编辑器js
  //               $this->ajaxReturn(array('url'=>$url));
  //           }
  //       }
  //   }
  //
   public function chat(){
     return $this->fetch();
   }
  //
  // public function a(){
  //   return $this->fetch();
  // }

  public function OneToOne(){
    return $this->fetch();
  }

  public function bindUid($client_id){
    Gateway::$registerAddress = '127.0.0.1:1236';
    // 假设用户已经登录，用户uid和群组id在session中
    $uid      = session('qt_userId');
    // client_id与uid绑定
    $res = Gateway::bindUid($client_id, session('qt_userId'));
    
  }

  public function sendMsg($msg, $uid){
    // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
    Gateway::$registerAddress = '127.0.0.1:1238';
    $messages = ['type'=>'msg', 'content'=>$msg, 'time'=>time()];
    // 向任意uid的网站页面发送数据
    Gateway::sendToUid($uid, json_encode($messages));

  }
  
  public function sharered () {
    (new Order())->shareRedAccount(1);
  }
}

 ?>
