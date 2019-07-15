<?php
namespace app\admin\controller;
use think\Session;
use think\Db;
class Index extends AdminBase{
    protected function _initialize(){
      parent::_initialize();
    }
    public function index()
    {
        // Session::set('admin_id', 1);
        $res = Db::name('user')->where('is_employ_agent', 1)->find();
        $hasEmployAgent = $res ? true : false;
        $this->assign('hasEmployAgent', $hasEmployAgent);
        return $this->fetch();
    }

    public function home () {
      // dump();
      //用户
      $user_num = Db::name('user')->count();
      $order = Db::name('order');
      //分销记录
      $sile_num = Db::name('divide_note')->field(['id'])->count();

      //订单
      $order_num = Db::name('order')->field(['order_id'])->count();

      //交易额
      $total_money = Db::name('order')->where("order_status IN (2,3,5,7)")->sum('order_amount');

      //订单详情统计
      $order_detail = [];
      $order_detail['s1'] = Db::name('order')->field(['order_id'])->where("order_status", 1)->count();
      $order_detail['s2'] = Db::name('order')->field(['order_id'])->where("pay_status=2 AND shipping_status =1 AND order_status in(1,2)")->count();
      $order_detail['s3'] = Db::name('order')->field(['order_id'])->where("pay_status = 1 AND order_status = 1")->count();
      $order_detail['s4'] = Db::name('order')->field(['order_id'])->where("order_status", 3)->count();
      $order_detail['s5'] = Db::name('order')->field(['order_id'])->where("order_status", 6)->count();

      //商品信息统计
      $goods_info = [];
      $goods = Db::name('goods');
      $goods_info['total'] = Db::name('goods')->field(['id'])->count();
      $goods_info['on'] = Db::name('goods')->field(['id'])->where("is_on_sale=1")->count();
      $goods_info['not_on'] = Db::name('goods')->field(['id'])->where("is_on_sale=0")->count();
      $goods_info['total_com'] = Db::name('comment')->field(['comment_id'])->count();
      
      // 经销商
      $agent = [];
      $agent['fir'] = Db::name('user')->where('agent_type', 1)->count();
      $agent['second'] = Db::name('user')->where('agent_type', 2)->count();
      $agent['third'] = Db::name('user')->where('agent_type', 3)->count();
      $agent['forth'] = Db::name('user')->where('agent_type', 4)->count();
      $agent['vip'] = Db::name('user')->where('agent_type', 0)->count();
      return $this->fetch('home',[
        'user_num'  => $user_num,
        'sile_num'  => $sile_num,
        'order_num' => $order_num,
        'total_money'=> $total_money,
        'order_detail' => $order_detail,
        'goods_info'  => $goods_info,
        'agent' => $agent
      ]);
    }

    /**
     * 退出
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-26T14:33:17+080
     */
    public function login_out(){
      Session::delete('admin_id');
      Session::delete('admin_info');
      $this->success('退出成功', url('admin/login/index'));
    }
}
