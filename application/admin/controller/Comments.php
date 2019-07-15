<?php
namespace app\admin\controller;

use app\admin\controller\AdminBase;
use think\Db;
use think\Log;
use think\Config;
use app\common\model\Comment as comment;

class Comments extends AdminBase
{
  public function getcomment($evaluate = '', $goods_rank = '', $service_rank = '', $is_img = "", $page =1 )
    {
         $where=[];
        if ($evaluate) {

            $where['a.evaluate'] = [
                '=',
                $evaluate
            ];
        }

        if ($goods_rank) {

            $where['a.goods_rank'] = [
                '=',
                $goods_rank
            ];
        }

        if ($evaluate) {

            $where['a.service_rank'] = [
                '=',
                $service_rank
            ];
        }

        if ($is_img) {

            $where['a.is_img'] = [
                '=',
                $is_img
            ];
        }
     $result = Db::name('comment')->alias('a')->field(['a.comment_id','a.goods_id ','a.content','a.goods_rank','a.service_rank','a.evaluate','a.user_id','a.add_time','c.goods_sn','c.goods_name','b.phone'
     ])->join('__ORDER_GOODS__ c','c.rec_id=a.rec_id', 'LEFT')->join('__USER__ b','b.id=a.user_id')->where($where)->order("a.add_time")->paginate(Config::get('pageSize'), false, ['page'=>$page]);
      // dump(Db::name('comment')->getLastSql());
      // dump($result);
     //  exit;
        if ($result) {
            $this->assign('info',$result);
            Log::write('后台：评论信息查询成功！', 'info');
            return $this->fetch('order/comment_list');

        } else {

            $this->assign('info',$result);
            Log::write('后台:评论信息查询失败！','info');
            return $this->fetch('order/comment_list');
        }
    }

    function delcomment($id = 0, $ids = [])
    {
        $id = $id ? $id : $ids;
        if (comment::destroy($id)) {
            $this->success("删除成功！");
        }

        $this->error("删除失败！");
    }
}

?>