<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/31 0031
 * Time: 下午 8:42
 */
namespace app\admin\controller;

use think\Config;
use think\Db;
use think\Session;
use app\common\model\Message as MessageModel;
use GatewayClient\Gateway;

class Message extends AdminBase
{

    /**
     * 把消息client_id与uid绑定
     *
     * @param [type] $client_id
     *            [消息客户端id]
     *
     * @return [type] [description]
     *         @Author : slade
     *         @DateTime :2017-05-16T10:11:23+080
     */
    public function bindUid($client_id)
    {
        Gateway::$registerAddress = Config::get('Message_Register_Address');
        // 假设用户已经登录，用户uid和群组id在session中
        // client_id与uid绑定
        $res = Gateway::bindUid($client_id, '-2');
        if ($res) {
            return $this->ajax(2000, '进入消息服务器成功');
        } else {
            return $this->ajax(4000, '进入消息服务器失败，请刷新重试');
        }
    }

    /**
     * 获取未读消息列表
     *
     * @author : slide
     *
     */
    public function get_not_read_message()
    {
        $message_list = Db::name('message_consignee')->alias('mc')
            ->field([
            'mc.sender_id',
            'u.nickname',
            'u.phone',
            'u.userimg'
        ])
            ->join('__USER__ u', 'u.id=mc.sender_id', 'LEFT')
            ->where([
            'consignee_id' => - 2,
            'message_status' => 0
        ])
            ->group('sender_id')
            ->order('create_time DESC')
            ->select();
        $code = $message_list ? 2000 : 4000;
        $msg = $message_list ? '新消息获取成功' : '新消息获取失败';
        return $this->ajax($code, $msg, '', $message_list);
    }

    /**
     * 跳转聊天搜索
     *
     * @author :chase
     *
     */
    public function chatin()
    {
        return $this->fetch('messageList/chatin');
    }

    public function chatlist($id = '', $keyword = '')
    {
        $where = [];

        if ($id) {

            $where['ch.id'] = $id;
        }

        if ($keyword) {

            $where['c.nickname|ch.phone'] =['like', '%'.$keyword.'%'];
        }

        $user_info = Db::name('user')->alias('ch')
            ->field([
            'ch.id',
            'c.nickname',
            'ch.phone',
            'c.headimgurl'
        ])
            ->join('__USER_WEICHAT_INFO__ c', 'ch.bindwx=c.id','LEFT')
            ->where($where)
            ->select();

        if ($this->request->isAjax()) {
            return $this->ajax(2000, '查询成功', '', $user_info);
        } else {
            $this->assign('info', $user_info);
            return $this->fetch();
        }
    }



    /**
     * 最近三天联系人
     * @author  chase
     */
    public function newchat($agent_type='',$keyword = ''){

        $where='';

        if($agent_type){


            if($agent_type==-1){
                      $agent_type=0;
            }

          $where="AND `u`.`agent_type`={$agent_type}";

        }

        if ($keyword) {

            $where = " AND 'wx.nickname' LIKE '%".$keyword."%' OR u.phone LIKE '%".$keyword."%'";
        }
        $sql = "SELECT
	`chs`.`message_id`,
	`chs`.`sender_id`,
	`chs`.`consignee_id`,
	`chs`.`type`,
    `chs`.`message_status`,
	`chs`.`create_time`,
	`u`.`id`,
	`u`.`phone`,
	`u`.`userimg`,
	`u`.`nickname`,
	`u`.`bindwx`,
	`wx`.`nickname` AS `wx_nickname`,
	`wx`.`headimgurl`
FROM
	`ty_message_consignee` `chs`
LEFT JOIN `ty_user` `u` ON CASE `chs`.`sender_id`
WHEN (-2) THEN
	`chs`.`consignee_id` = `u`.`id`
ELSE
	`chs`.`sender_id` = `u`.`id`
END
LEFT JOIN `ty_user_weichat_info` `wx` ON `wx`.`id` = `u`.`bindwx`
WHERE `chs`.`sender_id` =- 2
OR `chs`.`consignee_id` =- 2
AND `chs`.`create_time` > UNIX_TIMESTAMP(DATE_SUB(NOW() ,INTERVAL 3 DAY))

 {$where}
GROUP BY
	`chs`.`sender_id`
ORDER BY
	`chs`.`create_time` desc";
//echo $sql;
 $user_info=Db::query($sql );

       //$result=Db::name('message_consignee')->alias('chs')->field([
        //    'chs.*'
       // ])
       // ->where('chs.sender_id',-2)
       // ->whereOr('chs.consignee_id',-2)
        //->whereTime('chs.create_time','-72 hours')
       // ->order('chs.create_time','desc')
       // ->group('chs.sender_id')
       //->select();
		//$user_ids = reset_cobntact($result);


		// 用户信息
		//$user_info = Db::name('user')->alias('u')->field(['u.id','u.phone','u.userimg', 'u.nickname','u.bindwx','wx.nickname as wx_nickname', 'wx.headimgurl'])->join('__USER_WEICHAT_INFO__ wx', 'wx.id=u.bindwx','left')->where($where)->where("u.id IN ($user_ids)")->select();

		//$user_info = convert_arr_key($user_info, 'id');
		//dump($user_info);
		//exit;

		//dump(Db::name('message_consignee')->getLastSql());
       if ($this->request->isAjax()) {
           return $this->ajax(2000, '查询成功', '', $user_info);
       } else {
           $this->assign('info', $user_info);
           return $this->fetch();
       }

    }





}
