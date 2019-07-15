<?php
use think\Db;
use think\Cache;
use think\Config;
use think\Url;
use think\Session;
/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 */
function convert_arr_key($arr, $key_name)
{
  $arr2 = array();
  foreach($arr as $key => $val){
    $arr2[$val[$key_name]] = $val;
  }
  return $arr2;
}

/**
 * 数组层级缩进转换
 * @param array $array
 * @param int   $pid
 * @param int   $level
 * @return array
 */
function array2level($array, $pid = 0, $level = 1, $value = []) {
  static $list = [];
  foreach ($array as $v) {

    if ($v['pid'] == $pid) {
      $v['level'] = $level;
      $v['parent_path'] = isset($value['parent_path']) ? $value['parent_path'].'_'.$pid : '0_'.$v['pid'];
      $list[]     = $v;
      array2level($array, $v['id'], $level + 1, $v);
    }
  }
  return $list;
}

function setMenu($data, $pid = '0', $level=1){
  $tree = array();
  foreach($data as $v){
    if($v['pid'] == $pid){
      $level ++;
      $v['level'] = $level;
      $v['child'] = setMenu($data, $v['id'], $level);
      $tree[] = $v;
    }
  }
  return $tree;
}

function area2arr($arr, $parent_id=0, $field = 'parent_id'){
  static $list = [];
  // dump($parent_id);
  foreach ($arr as $v) {
    if ($v[$field] == $parent_id) {
      // dump(area2arr($arr, $parent_id = $v['id'], $field));exit;
      $v['child'] = area2arr($arr, $parent_id = $v['id'], $field);
      $list[]     = $v;
      dump($list);
    }
  }
  return $list;
}
/**
 * @param $arr
 * @param $key_name
 * @param $key_name2
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名 数组指定列为元素 的一个数组
 */
function get_id_val($arr, $key_name,$key_name2)
{
  $arr2 = array();
  foreach($arr as $key => $val){
    $arr2[$val[$key_name]] = $val[$key_name2];
  }
  return $arr2;
}

/**
 * 多个数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function combineDika() {
  $data = func_get_args();
  $data = current($data);
  $cnt = count($data);
  $result = array();
  $arr1 = array_shift($data);
  foreach($arr1 as $key=>$item)
  {
    $result[] = array($item);
  }

  foreach($data as $key=>$item)
  {
    $result = combineArray($result,$item);
  }
  return $result;
}
/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
 */
function combineArray($arr1,$arr2) {
  $result = array();
  foreach ($arr1 as $item1)
  {
    foreach ($arr2 as $item2)
    {
      $temp = $item1;
      $temp[] = $item2;
      $result[] = $temp;
    }
  }
  return $result;
}

/**
 * 分类是否是顶级分类
 * @param    [type]                   $cate_id [description]
 * @return   {boolean                 [description]
 * @Author:  slade
 * @DateTime :2017-04-06T15:28:44+080
 */
function cate_is_first($cate_id){
  $category = Db::name('goods_category');
  $res = $category->find($cate_id);
  return $res ? ($res['pid'] > 0 ? false : true) : NULL;
}
/**
 * 分类是否由下级
 * @param    [type]                   $cate_id [description]
 * @return   {boolean                 [description]
 * @Author:  slade
 * @DateTime :2017-04-06T15:38:16+080
 */
function cate_has_child($cate_id){
  $category = Db::name('goods_category');
  $res = $category->where("pid={$cate_id} AND status=1")->find();
  return $res ? true : false;
}
/**
 * [return_child_cate 获取下级分类]
 * @param    [type]                   $cate_id [description]
 * @return   {[type]                  [description]
 * @Author:  slade
 * @DateTime :2017-04-06T16:08:24+080
 */
function return_child_cate($cate_id){
  $category = Db::name('goods_category');
  $res = $category->field('id')->where("pid={$cate_id} AND status=1")->select();
  // dump($res);
  $result = array_column($res, 'id');
  return $result;
}

/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method="GET", $postfields = null, $headers = array(), $debug = false) {
  $method = strtoupper($method);
  $ci = curl_init();
  /* Curl settings */
  curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
  curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
  curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
  curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
  curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
  switch ($method) {
    case "POST":
      curl_setopt($ci, CURLOPT_POST, true);
      if (!empty($postfields)) {
        $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
        curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
      }
      break;
    default:
      curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
      break;
  }
  $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
  curl_setopt($ci, CURLOPT_URL, $url);
  if($ssl){
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
  }
  //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
  curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
  curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ci, CURLINFO_HEADER_OUT, true);
  /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
  $response = curl_exec($ci);
  $requestinfo = curl_getinfo($ci);
  $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
  if ($debug) {
    echo "=====post data======\r\n";
    dump($postfields);
    echo "=====info===== \r\n";
    dump($requestinfo);
    echo "=====response=====\r\n";
    dump($response);
  }
  curl_close($ci);
  return $response;
  //return array($http_code, $response,$requestinfo);
}

/**
 * 发送短信
 * @param $mobile  手机号码
 * @param $content  内容
 * @return bool
 */
// $dev_id = "b7d5107d782d8833v35047d4a4wt089b";
// $dev_key = "e35c7a8mqv0144e79ef9k41a31282f21";
// $rec_num = "13800138000,18900189000,18600186000";
// $sign = $dev_id.$dev_key.$rec_num;
//
// $para = "dev_id=".$dev_id;
// $para.= "&sign=".md5($sign);
// $para.= "&sms_template_code=test";
// $para.= "&rec_num=".$rec_num;
// $para.= "&sms_param={\"code\":\"123456\"}";
//
// $curl = curl_init("http://www.xinxinke.com/api/send");
// curl_setopt($curl, CURLOPT_HEADER, 0);
// curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($curl, CURLOPT_POST, true);
// curl_setopt($curl, CURLOPT_POSTFIELDS, $para);
// echo(curl_exec($curl));
// curl_close($curl);

function sendSMS($mobile, $smsId, $content, $sms_param)
{
  // dump($sms_param);
  // dump($content);
  // dump($smsId);
  // dump($mobile);exit;
  if(!$mobile || $mobile == '' || !$smsId || $smsId == '' || !$content || $content==''||!$sms_param||$sms_param==''){
    return false;
  }

  $data = array
  (
    'dev_id'            => Config::get('SMS_DEV_ID'),
    'sign'              => md5(Config::get('SMS_DEV_ID').Config::get('SMS_DEV_KEY').$mobile),
    'rec_num'           => $mobile,
    'sms_param'         => $sms_param,
    'sms_template_code' => $smsId,
    'sms_sign'          => Config::get('SMS_SIGN')
  );
  //即时发送
  $res = httpRequest(Config::get('SMS_SEND_URL'),'POST',$data);//POST方式提交
  // dump($res);
  if($res){
    return $res;
  }else{
    return false;
  }
}
/**
 * 生成随机数
 * @param    integer                  $length [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-04-13T11:12:40+080
 */
function generate_code($length = 6) {
  return rand(pow(10,($length-1)), pow(10,$length)-1);
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id,$width,$height){

  if(empty($goods_id))
    return '';
  //判断缩略图是否存在
  $path = "public/upload/goods/thumb/$goods_id/";
  $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}";

  // 这个商品 已经生成过这个比例的图片就直接返回了
  if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
  if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
  if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
  if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';

  $original_img = Db::name('Goods')->where("id", $goods_id)->value('thumb');
  if(empty($original_img)) return '';
  // dump($original_img);
  $original_img = '.'.$original_img; // 相对路径
  if(!file_exists($original_img)) return '';

  //$image = new \think\Image();
  $image = \think\Image::open($original_img);

  $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
  //生成缩略图
  if(!is_dir($path))
    mkdir($path,0777,true);

  //参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
  $image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存

  //图片水印处理
  $water = Config::get('water');
  if($water['is_mark']==1){
    $imgresource = './'.$path.$goods_thumb_name;
    if($width>$water['mark_width'] && $height>$water['mark_height']){
      if($water['mark_type'] == 'img'){
        $image->open($imgresource)->water(".".$water['mark_img'],$water['sel'],$water['mark_degree'])->save($imgresource);
      }else{
        //检查字体文件是否存在
        if(file_exists('./zhjt.ttf')){
          $image->open($imgresource)->text($water['mark_txt'],'./zhjt.ttf',20,'#000000',$water['sel'])->save($imgresource);
        }
      }
    }
  }
  return '/'.$path.$goods_thumb_name;
}

/**
 * 支付完成修改订单
 * @param $order_sn 订单号
 * @param array $ext 额外参数
 * @return bool|void
 */
function update_pay_status($order_sn,$ext=array())
{
  $order = Db::name('order');
  $order_res = Db::name('order')->where("order_sn='".$order_sn."'")->find();
  $count = $order->where("order_sn='".$order_sn."' and pay_status = 1")->count();
  if($count == 0) return false;
  $res = $order->where("order_sn='".$order_sn."'")->update(array('pay_status'=>2,'pay_time'=>time()));
  // 记录订单操作日志
  if(array_key_exists('admin_id',$ext)){
    logOrder($order_res['order_id'], $ext['note'], '付款成功', $ext['admin_id']);
  }else{
    logOrder($order_res['order_id'], '订单付款成功', '付款成功', $order_res['user_id']);
  }
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id, $action_note, $status_desc, $user_id = 0)
{
  // $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
  // dump($order);exit;
  $order = Db::name('order')->where("order_id=".$order_id)->find();
  $action_info = array(
    'order_id'        =>$order_id,
    'action_user'     =>$user_id,
    'order_status'    =>$order['order_status'],
    'shipping_status' =>$order['shipping_status'],
    'pay_status'      =>$order['pay_status'],
    'action_note'     =>$action_note,
    'status_desc'     =>$status_desc, //''
    'log_time'        =>time(),
  );
  return Db::name('order_action')->insert($action_info);
}


/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $type     			类型1进账2出账
 * @param   string  $desc    变动说明
 * @param   float   distribut_money 分佣金额
 * @return  bool
 */
function accountLog($user_id, $user_money = 0, $type = 1, $desc = ''){

  /* 插入帐户变动记录 */
  $account_log = array(
    'user_id'       => $user_id,
    'user_money'    => $user_money,
    'type'    			=> $type,
    'createtime'   	=> time(),
    'note'   				=> $desc,
  );
  $money = $type == 1 ? $user_money : $user_money * (-1);
  $money = round($money, 2);
  /* 更新用户信息 */
  $sql = "UPDATE ty_user SET user_vb = user_vb + ($money) WHERE id = $user_id";
  if( Db::execute($sql)){
    Db::name('userVbNote')->insert($account_log);
    $vb = Db::name('user')->where("id", $user_id)->value('user_vb');
    $change = $type==1?'增加':'减少';
    L($user_id.'费用变动'.$change.'：'.$money);
    // //判断需不需要短信通知 && 模板消息通知
    // $sms_add_order = cache_data('site_config')['do_get_money_form_other'];
    $template_add_order = cache_data('site_config')['template_account_change'];
    // $order_info = $order_res;
    // if($sms_add_order){
    //   //短信通知
    //   $sms = new Sms();
    //   $mobile = Db::name('user')->where('id', $order_info['user_id'])->value('phone');
    //   $scren = 'do_get_money_form_other';
    //   $send_sms_res = $sms->sendSMS($mobile, $scren, [Config::get('SMS_SIGN'), $order_info['order_sn'], $res['consignee'], $res['invoice_no']]);
    //   if($send_sms_res){
    //     Log::write('支付短信:'.$order_info['order_sn'].'短信发送成功','info');
    //   }else{
    //     Log::write('支付短信:'.$order_info['order_sn'].'短信发送失败','info');
    //   }
    // }



    if($template_add_order){

      //模板消息通知
      $wechat = new \app\home\controller\Weichat();
      $template_send_re = $wechat->sendTemplateMsg(WEB_DOMAIN.url('home/user/mywallet'),'ACOUNT_CHANGE', ["您在".Config::get('SMS_SIGN')."账户资金".$change."!",date("Y年m月d日 H:i"),$money,"",'￥'.$vb,"查看详情"], $user_id);
      if($template_send_re){
        \Think\Log::write('账户变动:'.$user_id.'模板消息发送成功','info');
      }else{
        \Think\Log::write('账户变动:'.$user_id.'模板消息发送失败','info');
      }
    }
    return true;
  }else{
    return false;
  }
}

/**
 * 微信模板消息组装
 * @param    [array]                   $data [需要组装的数据]
 * @return   [array]                   [组装完成的数据]
 * @Author:  slade
 * @DateTime :2017-04-19T14:49:36+080
 */
function weichat_template_data($type = '', $data = []){
  if(!$type || !$data || $type == '' || empty($data)) return [];
  $result = [];
  $template_field = [];
  switch (strtoupper($type)) {
    case 'ADD_ORDER':
      $template_field = ['first', 'keyword1','keyword2', 'remark'];
      break;
    case 'ACOUNT_CHANGE':
      $template_field = ['first', 'date','adCharge', 'type', 'cashBalance', 'remark'];
      break;
    case 'PAY_SUCCESS':
      $template_field = ['first', 'keyword1','keyword2', 'keyword3', 'keyword4', 'keyword5', 'remark'];
      break;
    case 'SEND_GOODS':
      $template_field = ['first', 'keyword1','keyword2', 'keyword3', 'keyword4', 'remark'];
      break;
    case 'CONFIRM_GOODS':
      $template_field = ['first', 'keyword1','keyword2', 'keyword3', 'keyword4', 'keyword5', 'remark'];
      break;
    case 'TASK_PROCESSING':
      $template_field = ['first', 'keyword1','keyword2', 'remark'];
      break;
    case 'PAY_SUC':
      $template_field = ['first', 'money','product', 'remark'];
      break;
    case 'COUNSEL':
      $template_field = ['first', 'keyword1','keyword2', 'remark'];
      break;
    default:
      # code...
      break;
  }
   //dump($data);
  //dump($template_field);
  //组装数据
  foreach ($template_field as $key => $value) {
    $result[$value] = $data[$key];
  }
  return $result;
}
/**
 * 改变库存
 * @param    [type]                   $goods_id [商品]
 * @param    [type]                   $num      [库存]
 * @param    [type]                   $order    [order数据]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-04-19T09:58:48+080
 */
function changeStore($goods_id, $num, $spec_key){
  $goodsDb = Db::name('Goods');
  $goods_info = $goodsDb->field(['spec_config'])->find($goods_id)['spec_config'];
  $info = json_decode($goods_info, true);

  $order_spec = implode('_',explode('|',$spec_key));
  // dump($info);exit;
  if($info){
    $spec_id = array_keys($info);
    foreach ($info as $k => &$v) {
      // dump($order_spec);exit;
      if($k == $order_spec){
        //有规格
        if($v['store_count'] - $num >= 0){
          $v['store_count'] = $v['store_count'] - $num;
        }else{
          return false;
        }
        break;
      }
    }
  }

  $res = $goodsDb->where("id=".$goods_id)->update(['spec_config'=>json_encode($info, true)]);
  $result = Db::name('Goods')->where("id = {$goods_id}")->setDec('store_count',$num);
  // dump($res);
  // dump($result);exit;
  return $result;
}

/**
 *  post提交数据
 * @param  string $url 请求Url
 * @param  array $datas 提交的数据
 * @return url响应返回的html
 */
function sendPost($url, $datas) {
  $temps = array();
  foreach ($datas as $key => $value) {
    $temps[] = sprintf('%s=%s', $key, $value);
  }
  $post_data = implode('&', $temps);
  $url_info = parse_url($url);
  // dump($url);
  // dump($url_info);
  if(empty($url_info['port']))
  {
    $url_info['port']=80;
  }
  $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
  $httpheader.= "Host:" . $url_info['host'] . "\r\n";
  $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
  $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
  $httpheader.= "Connection:close\r\n\r\n";
  $httpheader.= $post_data;
  $fd = fsockopen($url_info['host'], $url_info['port']);
  fwrite($fd, $httpheader);
  $gets = "";
  $headerFlag = true;
  while (!feof($fd)) {
    if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
      break;
    }
  }
  while (!feof($fd)) {
    $gets.= fread($fd, 128);
  }
  fclose($fd);

  return $gets;
}


/**
 * 缓存读写
 * @param    [type]                   $key   [读取缓存的key]
 * @param    [type]                   $data  [写入的数据]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-04-20T09:02:37+080
 */
function cache_data($key, $data = []){
  $param_key = explode('.', $key);
  if(empty($data)){
    //读取缓存
    if(count($param_key) > 1){
      //有tag
      return Cache::tag($param_key[0]).get($param_key[1]);
    }else{
      //一个
      return Cache::get($key);
    }
  }else{
    //更新缓存
    return Cache::set($param_key[0], $data);
  }
}

/**
 * Url生成
 * @param string        $url 路由地址
 * @param string|array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
if(!IS_CLI) {
  function url($url = '', $vars = '', $suffix = true, $domain = false)
  {
    $con = count(explode('admin', $url));
    // dump($con);
    if($con <= 1 ){
      // dump($con <= 1);
      $userId = Session::get('qt_userId') ? Session::get('qt_userId') : '';
      // dump($userId);
      $url_uid = input('uid') ? input('uid') : false;
      if(is_array($vars) && !in_array('uid', $vars)){
        $vars['uid'] = $userId ? $userId : $url_uid;
      }
      if(is_string($vars) && !strpos($vars,'uid')){
        if($vars == ''){
          $vars .= $userId? "uid=".$userId : "uid=".$url_uid;
        }else{
          $vars .= $userId? "uid=".$userId : "uid=".$url_uid;
        }
      }
    }


    return Url::build($url, $vars, $suffix, $domain);
  }

}
/**
 * 删除目录及目录下的所有文件
 * @param    [type]                   $dirName [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-04-27T15:02:00+080
 */
function delDirAndFile( $dirName )
{
  if ( $handle = opendir( ROOT_PATH .$dirName ) ) {
    while ( false !== ( $item = readdir( $handle ) ) ) {
      if ( $item != "." && $item != ".." ) {
        if ( is_dir( $dirName."/".$item ) ) {
          delDirAndFile( $dirName."/".$item );
        } else {
          if( unlink( ROOT_PATH.$dirName."/".$item ) ) return true;
        }
      }
    }
    closedir( $handle );
    if( rmdir( $dirName ) ) {
      return true;
    }else{
      return false;
    }
  }
}
// 定义一个函数getIP() 客户端IP，
function getIP(){
  if (getenv("HTTP_CLIENT_IP"))
    $ip = getenv("HTTP_CLIENT_IP");
  else if(getenv("HTTP_X_FORWARDED_FOR"))
    $ip = getenv("HTTP_X_FORWARDED_FOR");
  else if(getenv("REMOTE_ADDR"))
    $ip = getenv("REMOTE_ADDR");
  else $ip = "Unknow";

  if(preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip))
    return $ip;
  else
    return '';
}

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function adminLog($log_info){
  $add['log_time'] = time();
  $add['admin_id'] = session('admin_id');
  $add['log_info'] = $log_info;
  $add['log_ip'] = getIP();
  $add['log_url'] = request()->baseUrl() ;
  Db::name('admin_log')->insert($add);
}
/**
 * 记录分成
 * @param    [type]                   $data [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-03T16:07:26+080
 */
function divideNote($data){
  $data['user_id'] = $data['user_id'];
  $data['order_id'] = $data['order_id'];
  $data['commission'] = $data['commission'];
  $data['total_money'] = $data['total_money'];
  $data['level'] = $data['level'];
  $data['type'] = $data['type'];
  $data['order_info'] = isset($data['order_info']) ? $data['order_info'] : '';
  $data['user_info'] = isset($data['user_info']) ? $data['user_info'] : '';
  $data['createtime'] = time();
  Db::name('divide_note')->insert($data);
}

/**
 * 没有分成完的直接转入没有分成的表
 * @author: slide
 * @param $type  1、代理申请， 2、购物
 * @param $money 金额
 */
function change_over_to_not_divide($type, $money){
  $data['type'] = $type;
  $data['money'] = $money;
  $data['create_time'] = time();
  Db::name('not_divide')->insert($data);
}

/**
 * 微信菜单类型装换
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-05T12:03:12+080
 */
function weichatMenuType($type){
  $value = '';
  switch ($type) {
    case 'click':
      $value = 'key';
      break;
    case 'view':
      $value = 'url';
      break;
  }
  return $value;
}
/**
 * 微信菜单数据
 * @Author:  slade
 * @DateTime :2017-05-05T14:10:55+080
 */
function setWxMenu($data, $pid=0){
  $tree = array();
  foreach($data as $v){
    if($v['pid'] == $pid){
      $temp_child = setWxMenu($data, $v['id']);
      $v['sub_button'] = $temp_child;
      // if(!empty($temp_child)){
      //
      // }
      $v['type'] = $v['type'];
      $v['name'] = $v['name'];
      $v[weichatMenuType($v['type'])] = $v['value'];
      $tree[] = $v;
    }
  }
  return $tree;
}
/**
 * 简写日志写入
 * @param    [type]                   $msg  [description]
 * @param    [type]                   $type [description]
 * @Author:  slade
 * @DateTime :2017-05-16T11:22:18+080
 */
function L($msg, $type='info'){
  \Think\Log::write($msg, $type);
}

/**
 * 生成唯一的uuid
 *
 * @author     Anis uddin Ahmad
 * @param      string  an optional prefix
 * @return     string  the formatted uuid
 */
function uuid($prefix = '')
{
  $chars = md5(uniqid(mt_rand(), true));
  $uuid  = substr($chars,0,8) . '-';
  $uuid .= substr($chars,8,4) . '-';
  $uuid .= substr($chars,12,4) . '-';
  $uuid .= substr($chars,16,4) . '-';
  $uuid .= substr($chars,20,12);
  return $prefix . $uuid;
}

/**
 * 二维数组排序
 * @author: slide
 *
 * @param $arr
 *
 */
function array_arr_sort($arr,$field){
  $sort = array(
    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
    'field'     => $field,       //排序字段
  );
  //dump($arr);exit;
  $arrSort = array();
  foreach($arr AS $uniqid => $row){
    foreach($row AS $key=>$value){
      $arrSort[$key][$uniqid] = $value;
    }
  }
  //dump($arrSort);
  if($sort['direction']){
    array_multisort($arrSort[$sort['field']], constant($sort['direction']), $arr);
  }
  //dump($arr);
  return $arr;
}

function sort3wei($array, $field){
  foreach($array as $key=>$val){
    $new_array= array();
    $sort_array = array();
    foreach($val as $k=>$v){
      $sort_array[$k] = $v[$field];
    }
    asort($sort_array);//降序使用 arsort();
    reset($sort_array);

    foreach($sort_array as $k=>$v){
      $new_array[$k] = $array[$key][$k];
    }
    $array[$key] = $new_array;
  }
  return $array;
}

/**
 * 保存图片
 * @author: slide
 *
 * @param $path
 *
 */
function dlfile($file_url, $save_to)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 0);
  curl_setopt($ch,CURLOPT_URL,$file_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $file_content = curl_exec($ch);
  curl_close($ch);
  $downloaded_file = fopen($save_to, 'w');
  $res = fwrite($downloaded_file, $file_content);
  fclose($downloaded_file);

  return $res ? true : false;
}

/**
 * 解析url
 * @author: slide
 *
 */
function parse_query($query){
  $query_str = explode('&', $query);
  $data = [];
  foreach ($query_str as $k => $v){
    $temp = explode('=', $v);
    $data[$temp[0]] = $temp[1];
  }

  return $data;
}

/**
 * 删除某个值的数组想
 * @author: slide
 * @param $arr
 * @param $value
 *
 */
function removeArrValue($arr,$field, $value){
  if(!is_array($arr)) return [];

  foreach ($arr as $k => $v){
    if($v[$field] == $value){
      unset($arr[$k]);
    }
  }
  return $arr;
}
/**
 * 创建订单号码
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-05-13T16:54:42+080
 */
function careateTicket($prefix = ''){
  $order_id = '';
  while (true) {
    // 订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
    $order_id_main = date('YmdHis') . rand(10000000, 99999999);
    // 订单号码主体长度
    $order_id_len = strlen($order_id_main);
    $order_id_sum = 0;
    for ($i = 0; $i < $order_id_len; $i ++) {
      $order_id_sum += (int) (substr($order_id_main, $i, 1));
    }
    // 唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
    $order_id = $prefix . $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
    return $order_id;
  }
}

/**
 * 本月起始时间
 * @author: slide
 * @param $type
 * @return mixed
 *
 */
function thisMonth($type = '', $date = ''){
  $date = $date ? $date : time();
  $thismonth_start=mktime(0,0,0,date('m', $date),1,date('Y', $date));
  $thismonth_end=mktime(23,59,59,date('m', $date),date('t', $date),date('Y', $date));

  $data = [
    'start' => $thismonth_start,
    'end' => $thismonth_end
  ];

  return $type ? $data[$type] : $data;
}
/**
 * 本年开始和结束时间
 * @author: slide
 * @param $type
 * @return mixed
 *
 */
function thisYear($type){
  $thisYear_start = mktime(0,0,0, 1, 1, date('Y'));
  $thisYear_end = mktime(23, 59, 59, 12, 31, date('Y'));
  $data = [
    'start' => $thisYear_start,
    'end' => $thisYear_end
  ];

  return $type ? $data[$type] : $data;
}

/**
 * @param $year 给定的年份
 * @param $month 给定的月份
 * @param $legth 筛选的区间长度 取前六个月就输入6
 * @param int $page 分页
 * @return array
 */
function to_sex_month($date){
  $arr = array();
  $old_time = strtotime('-'.(date('m') - 1).' month');
  for($i = 1;$i <= 12; $i++){
    $date_data = getShiJianChuo(date('Y'), $i, true);

    if($date){
      $arr[] = explode('/',$date_data['begin'].'/'.$date_data['end']);
    }else{
      $arr[] = explode('/',date('Y-m-01',$date_data['begin']).'/'.date('Y-m-d',$date_data['end']));
    }
  }
  return $arr;
}

function getShiJianChuo($nian=0,$yue=0, $date = false){
  if(empty($nian) || empty($yue)){
    $now = time();
    $nian = date("Y",$now);
    $yue =  date("m",$now);
  }
  if($date){
    $time['begin'] = mktime(0,0,0,$yue,1,$nian);
    $time['end'] = mktime(23,59,59,($yue+1),0,$nian);
  }else{
    $time['begin'] = date('Y-m-d', mktime(0,0,0,$yue,1,$nian));
    $time['end'] = date('Y-m-d', mktime(23,59,59,($yue+1),0,$nian));
  }
  return $time;
}


/**
 * 求两个已知经纬度之间的距离,单位为米
 *
 * @param lng1 $ ,lng2 经度
 * @param lat1 $ ,lat2 纬度
 * @return float 距离，单位米
 */
function getdistance($lng1, $lat1, $lng2, $lat2) {
  // 将角度转为狐度
  $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
  $radLat2 = deg2rad($lat2);
  $radLng1 = deg2rad($lng1);
  $radLng2 = deg2rad($lng2);
  $a = $radLat1 - $radLat2;
  $b = $radLng1 - $radLng2;
  $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
  return $s;
}

/**
 * 生成需要长度的字符
 * @author slide
 * @param int $length 需要的字符长度
 * @return string 返回随机字符
 */
function generate_str( $length = 32 ) {
  // 字符集，可任意添加你需要的字符
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $str ='';
  for ( $i = 0; $i < $length; $i++ )
  {
    $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
  }
  return $str;
}

/**
 * 生成querystring
 * @methods
 * @desc
 * @author slide
 *
 * @param $arr
 *
 */
function urlStringfy($arr){
  $str = '';
  if(empty($arr)) return $str;

  foreach ($arr as $k => $v) {
    $str .= $k .'='.$v.'&';
  }

  return substr($str, 0, strlen($str) - 1);
}



