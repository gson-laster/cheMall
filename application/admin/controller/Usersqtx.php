<?php
namespace app\admin\controller;

use app\common\model\Usersqtx as usersqtxMdl;
use think\Config;

class Usersqtx extends AdminBase
{

    protected $usersqtx;

    protected function _initialize()
    {
        parent::_initialize();
        $this->usersqtx = new usersqtxMdl();
    }

    function getusertxlist($page = 1, $createtime = "", $real_name = "")
    {
        $where = [];

        if ($createtime)

            $where['a.createtime'] = [
                'between',
                [
                    strtotime($createtime),
                    (strtotime($createtime) + 86400000)
                ]
            ];


        if ($real_name)
            $where['c.real_name'] = [
                'LIKE',
                "%{$real_name}%"
            ];

        $result = $this->usersqtx->alias('a')
            ->field([
            'b.user_vb',
            'b.real_id',
            'b.nickname',
            'a.isok',
            'a.isoktime',
            'a.sqtxprice',
            'a.userid',
            'a.sqtxfs',
            'c.real_name',
            'c.real_phone ',
            'c.bank_card_code',
            'c.bank_name',
            'c.alipay',
            'c.wxpay',
            'a.id',
            'c.user_id',
            'a.tax',
            'a.poundage',
            'a.actual',
            'a.createtime'
        ])
            ->join('__USER__ b', 'a.userid=b.id')
            ->join('__USER_REALNAME__ c', 'b.real_id=c.id')
            ->where($where)->order('a.id desc')->paginate(Config::get('pageSize'), false, [
        'page' => $page
    ]);
         //dump( $result);
        $this->assign("usertxlist", $result);
        return $this->fetch('user/sqtx_list');
    }

    function editorusersqtx($id = "", $isok = "")
    {
        $data = input();

        if (input('isok') == 1) {

            $result = $this->usersqtx->allowField(true)
                ->isUpdate(true)
                ->save($data);

            if ($result) {

                $this->success('成功');
            } else {

                $this->error('失败');
            }
        } else
            if (input('isok') == 2) {

                $re = $this->usersqtx->get(input('id'));

                if ($re['isok'] == 2) {
                    $this->error('亲，已经退过款了哦');
                } else {
                    $result = $this->usersqtx->allowField(true)
                        ->isUpdate(true)
                        ->save($data);

                    accountLog($re['userid'], $user_money = $re['sqtxprice'], $type = 1, $desc = '申请提现退款');
                }
                if ($result) {

                    $this->success('成功');
                } else {

                    $this->error('失败');
                }
            } else {

                $this->error('更新失败');
            }
    }
}

?>
