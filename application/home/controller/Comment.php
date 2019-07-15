<?php
namespace app\home\controller;

use app\home\controller\HomeBase;
use app\common\model\Comment as CommentModel;
use app\common\model\Goods as goodsModel;
use think\Db;

class Comment extends HomeBase
{

    protected $commentMdl;

    protected $goodsMdl;

    protected function _initialize()
    {
        parent::_initialize();

        $this->commentMdl = new CommentModel();
        $this->goodsMdl = new goodsModel();
    }

    function editorcomment($rec_id = "")
    {
        // $result = $this->goodsMdl->get($goods_id);
        $res = Db::name('order_goods')->alias('a')
            ->field([
            'a.*'
        ])
            ->where('rec_id', $rec_id)
            ->find();

        $res['thumb'] = goods_thum_images($res['goods_id'], 200, 200);

        $this->assign("goods", $res);

        return $this->fetch('reviews');
    }

    /**
     * *
     * 添加评论
     *
     * @param string $comment_id
     *            评论ID用于追加评论
     * @param string $order_id
     *            订单ID
     */
    function addcomment($comment_id = "", $order_id = "")
    {
        $data = input();
        if ($comment_id)

            $comment_id = $data['comment_id'];

        if ($order_id)

            $order_id = $data['order_id'];

        if ($comment_id != null) {

            $result = $this->commentMdl->allowField(true)
                ->isUpdate(true)
                ->save($data);
        } else {

            $result = $this->commentMdl->allowField(true)->save($data);
        }
        if ($result != 0) {

            $order = Db::name('order');

            $order->where('order_id', $order_id)->update([
                'order_status' => 5
            ]);

            Db::name('Goods')->where('id', $data['goods_id'])->setInc('comment_num', 1);

            $this->success("评价成功");
        } else {
            $this->error("评价失败");
        }
    }

    /**
     * 获取商品评论详情
     *
     * @return \think\response\Json|mixed
     */
    function getcomment($goods_id = "", $evaluate = "", $is_img = "", $pageSize = 10, $page = 1)
    {
        $data = input();

        $where = [];

        if ($goods_id)

            $where['a.goods_id'] = [
                '=',
                "{$data['goods_id']}"
            ];

        if ($evaluate) {

            if ($evaluate == 9) {

                $where['a.evaluate'] = [
                    '=',
                    "0"
                ];
            } else {

                $where['a.evaluate'] = [
                    '=',
                    "{$data['evaluate']}"
                ];
            }
        }

        if ($is_img)

            $where['a.is_img'] = [
                '=',
                "{$data['is_img']}"
            ];

        $result = $this->commentMdl->alias('a')
            ->field([
            'a.*',
            'b.userimg',
            'og.spec_key_name',
            'ng.nickname',
            'ng.headimgurl'
        ])
            ->join('__USER__ b', 'a.user_id=b.id', 'left')
            ->join('__ORDER_GOODS__ og', 'og.rec_id=a.rec_id', 'left')
            ->join('__USER_WEICHAT_INFO__ ng','b.bindwx=ng.id', 'left')
            ->where($where)
            ->paginate($pageSize, false, [
            'page' => $page
        ]);

        foreach ($result as $k => &$v) {
            if ($v['img']) {
                $v['img'] = explode('|$|', $v['img']);
            }
        }
        $re1 = $this->commentMdl->where('goods_id', $data['goods_id'])->count(); // 统计全部
        $re2 = $this->commentMdl->where('evaluate', 0)
            ->where('goods_id', $data['goods_id'])
            ->count(); // 差评统计
        $re3 = $this->commentMdl->where('evaluate', 1)
            ->where('goods_id', $data['goods_id'])
            ->count(); // 中评统计
        $re4 = $this->commentMdl->where('evaluate', 2)
            ->where('goods_id', $data['goods_id'])
            ->count(); // 好评统计
        $re5 = $this->commentMdl->where('is_img', 0)
            ->where('goods_id', $data['goods_id'])
            ->count(); // 无图统计
        $re6 = $this->commentMdl->where('is_img', 1)
            ->where('goods_id', $data['goods_id'])
            ->count(); // 有图统计

        if ($this->request->isAjax()) {

            return json([
                'code' => 1002,
                "data" => [
                    'comment' => $result,
                    'commentall' => $re1,
                    'commenteva0' => $re2,
                    'commenteva1' => $re3,
                    'commenteva2' => $re4,
                    'commentimg0' => $re5,
                    'commentimg1' => $re6
                ]
            ]);
        } else {
//            dump($result);
            $this->assign("comment", $result);
            $this->assign('commentall', $re1);
            $this->assign('commenteva0', $re2);
            $this->assign('commenteva1', $re3);
            $this->assign('commenteva2', $re4);
            $this->assign('commentimg0', $re5);
            $this->assign('commentimg1', $re6);
            $this->assign('evaluate', $evaluate);
            $this->assign('is_img', $is_img);
            return $this->fetch();
        }
    }
}

?>
