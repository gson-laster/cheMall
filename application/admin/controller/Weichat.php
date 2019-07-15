<?php
namespace app\admin\controller;

use think\Db;
use think\Config;

use EasyWeChat\Message\Text;
/**
 * 微信管理
 */
class Weichat extends AdminBase
{

  /**
   * 更新微信公众号的信息
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-12T11:39:05+080
   */
  public function wechatconfig(){
    $weichat_info = Db::name('weichat_info');
    if($this->request->isPost()){
      $data = $this->request->post();
      $rule = [
        ['appid', 'require', '请输入appid'],
        ['appsecret', 'require', '请输入appsecret'],
        ['token', 'require', '请输入token'],
        ['pay_id', 'require', '请填写商户ID'],
        ['pay_key', 'require', '请填写商户key'],
        ['wx_name', 'require', '请填写公众号名称']
      ];
      $validate_result = $this->validate($data, $rule);
      if( $validate_result !== true){
        $this->error($validate_result);
      }else{

        if($weichat_info->where("id=1")->update($data)){
          //写配置
          $wx_config = [
            'WX_APPID'            => $data['appid'],
            'WX_APPSTR'           => $data['appsecret'],
            'WX_TOKEN'            => $data['token'],
            'WX_PARTNERID'        => $data['pay_id'],
            'WX_PARTNERKEY'       => $data['pay_key'],
            'WX_CALLBACK'         => WEB_DOMAIN.url('home/Weichat/index')
          ];
          $wx_config_str = "<?php\nreturn ".var_export($wx_config, true).";";
          file_put_contents(CONF_PATH.'/wxConf.php', $wx_config_str);
          $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }
      }
    }else{
      $res = $weichat_info->find(1);
      $res['url'] = WEB_DOMAIN.url('home/Weichat/index');
      cache_data('wx_config', $res);
      return $this->fetch('wechatconfig', ['result'=>$res]);
    }
  }

  /**
   * 微信菜单管理  （包含列表|增加|删除）
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-12T12:13:00+080
   */
  public function weichatmenu($id=0){
    $menu = Db::name('weichat_menu');
    if($this->request->isPost()){
      //1、自定义菜单最多包括3个一级菜单，每个一级菜单最多包含5个二级菜单。
      // 2、一级菜单最多4个汉字，二级菜单最多7个汉字，多出来的部分将会以“...”代替。
      // 菜单的类型，公众平台官网上能够设置的菜单类型有view（跳转网页）、text（返回文本，下同）、img、photo、video、voice。使用API设置的则有8种，详见《自定义菜单创建接口》
      // https://mp.weixin.qq.com/wiki
      $data = $this->request->post();
      $rule = [
        ['level', 'require', 'level不能为空'],
        ['name', 'require', '菜单名称不能为空'],
        ['type', 'require', '请选择类型'],
        ['value', 'require', '地址或者关键词不能为空']
      ];
      $validate_result = $this->validate($data, $rule);
      if( $validate_result !== true){
        $this->error($validate_result);
      }
      $or = $data['level'] == 1 ? 3 : 5;
      
      $type = $id > 0 ? 2 : 1;
      if($type == 1){
        $count = $menu->where(['level'=>$data['level'], 'pid' => $data['pid']])->count();
        if($count > $or){
          $this->error('一级菜单只能3个,二级菜单只能5个');
        }
        $result = $menu->insert($data);
      }else{
        $result = $menu->where("id={$id}")->update($data);
      }
      if($result){
        $this->success('操作成功', url('weichatmenulist'), ['last_id'=>$menu->getLastInsID()]);
      }else{
        $this->error('操作失败');
      }
    }else{
      //查询菜单
      //一级 pid=0
      $list_first = $menu->where("level=1")->order("sort desc")->select();
      $list_first_res = convert_arr_key($list_first, 'id');

      //二级 pid
      $list_second = $menu->where("level=2")->order("sort desc")->select();
      $list_second_res = convert_arr_key($list_second, 'id');
      return $this->fetch('menu_list', ['list_first'=>$list_first_res,'list_second'=>$list_second_res]);
    }
  }

  /**
   * 微信文本回复管理  （包含列表|增加|删除）
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-12T12:31:55+080
   */
  public function weicahttext($page = 1, $id=0){
    $text = Db::name('weichat_text');
    if($this->request->isPost()){
      $data = $this->request->post();
      $rule = [
        ['keyword', 'require', '关键词不能为空'],
        ['text', 'require', '回复文本不能为空'],
      ];

      $validate_result = $this->validate($data, $rule);
      if( $validate_result !== true){
        $this->error($validate_result);
      }
      $res = Db::name('weichat_keywords')->where("keyword", $data['keyword'])->find();

      $type = $id > 0 ? 2 : 1;
      if($type == 1){
		  //dump($text);
		//dump($data);
        if($res){
          $this->error('关键词已经存在');
        }
        $result = Db::name('weichat_text')->insert($data);
		//dump(1);
        $keyword_data = [
          'keyword' => $data['keyword'],
          'type'    => 'TEXT',
          'msg_id'  => Db::name('weichat_text')->getLastInsID()
        ];
        Db::name('weichat_keywords')->insert($keyword_data);
      }else{
        $keyword_data = [
          'keyword' => $data['keyword'],
          'type'    => 'TEXT',
        ];
        Db::name('weichat_keywords')->where('msg_id', $data['id'])->update($keyword_data);
        $result = Db::name('weichat_text')->where("id=".$data['id'])->update($data);
      }
      if($result){
        $this->success('操作成功',url('weicahttext'));
      }else{
        $this->error('操作失败');
      }
    }else{
      $list = $text->order("id DESC")->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      return $this->fetch('text_list', ['list'=>$list]);
    }
  }

  /**
   * 图文消息                           （包含列表|增加|删除）
   * @param    integer                  $page [description]
   * @param    integer                  $id   [description]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-12T14:28:50+080
   */
  public function weichatimg($page=1, $id=0){
    $img = Db::name('weichat_img');
    if($this->request->isPost()){
      $data = $this->request->post();
      $rule = [
        ['keyword', 'require', '关键词不能为空'],
        ['title', 'require', '标题不能为空'],
        ['desc', 'require', '简介不能为空'],
        ['pic', 'require', '图片不能为空'],
        ['url', 'require', '文章地址不能为空'],
      ];
      $validate_result = $this->validate($data, $rule);
      if( $validate_result !== true){
        $this->error($validate_result);
      }

      $type = $id > 0 ? 2 : 1;
      if($type == 1){
        $result = Db::name('weichat_img')->insert($data);
        $res = Db::name('weichat_keywords')->where("keyword", $data['keyword'])->find();
        //dump(Db::name('weichat_keywords'));exit;
        if(!$res){
          $key_data = [
            'keyword' => $data['keyword'],
            'type'    => 'NEWS',
            'msg_id'  => Db::name('weichat_img')->getLastInsID()
          ];
          Db::name('weichat_keywords')->insert($key_data);
          //dump(Db::name('weichat_keywords'));exit;
        }
      }else{
        $keyword_data = [
          'keyword' => $data['keyword'],
          'type'    => 'NEWS',
        ];
        Db::name('weichat_keywords')->where('msg_id', $id)->update($keyword_data);
        $result = Db::name('weichat_img')->where("id={$id}")->update($data);
      }
      if($result){
        $this->success('操作成功');
      }else{
        $this->error('操作失败');
      }
    }else{
      $list_res = $img->order('id DESC')->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      return $this->fetch('img_list',['list'=>$list_res]);
    }
  }

  /**
   * 根据表明和id获取信息
   * @param    string                   $table [表名  值： text|img|menu]
   * @param    integer                  $id    [信息学id]
   * @return   [json]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-12T12:36:07+080
   */
  public function getWeichatById($table = '', $id = 0){
    if(!$table || !$id){
      $this->error('缺少必要参数');
    }else{
      $table = Db::name('weichat_'.$table)->find($id);
      if($table){
        $this->success('获取成功','', $table);
      }else{
        $this->error('获取失败');
      }
    }
  }
  /**
   * 删除记录
   * @param    string                   $table [不带前缀的表名]
   * @param    integer                  $id    [主键id 删除单个传id，多个传id,id,id]
   * @return   [type]                   [description]
   * @Author:  slade
   * @DateTime :2017-04-17T09:11:50+080
   */
  public function delete($table = '', $id = 0, $ids=[]){
    $id = $id ? $id : $ids;
    if(!$table || !$id){
      $this->error('缺少必要参数');
    }else{
      if(Db::name('weichat_'.$table)->delete($id)){
        $this->success('删除成功','', $table);
      }else{
        $this->error('删除失败');
      }
    }
  }




  /**
   * 发送模板消息
   *
   * @param    string $url  [详细地址]
   * @param    string $type [场景]
   * @param    [array]                  $data [传输的数据]
   *
   * @return   [int]                   [发送id or false]
   * @Author   :  slade
   * @DateTime :2017-04-19T15:26:51+080
   */
  public function sendTemplateMsg ( $url = '', $type = '', $data = [], $userId = '' ) {
      if ( !$url || !$type || $type == '' || !$data || empty( $data ) ) {
          return false;
      }
      //L('测试', 'info');
      $user_id = $userId;
      $user_wx = Db::name('user')->where( "id", $user_id )->value( 'bindwx' );
      L('测试2_'.$user_id.'_'.$user_wx, 'info');
      if ( $userId && ( !$user_wx || $user_wx == '' || is_null( $user_wx ) ) ) {
          return false;
      }
      //L('测试2', 'info');
      $userOpenId = Db::name( 'userWeichatInfo' )->where( "id", $user_wx )->value( "openid" );
      if ( !$userOpenId || is_null( $userOpenId ) || $userOpenId == '' ) {
          return false;
      }
      L('OpendId'.$userOpenId, 'info');
      $order_data  = weichat_template_data( $type, $data );
      $notice      = $this->easy_app->notice;
      $template_id = Config::get( 'WECHAT_TEAMPLATE' )[ $type ];
      $messageId   = $notice->to( $userOpenId )->url( $url )->withTemplateId($template_id )->withData( $order_data )->send();
      if ( $messageId->errcode == 0 && $messageId->errmsg == 'ok' ) {
          return $messageId->msgid;
      } else {
          return false;
      }
  }



}
