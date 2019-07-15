<?php
namespace app\home\controller;

use app\common\model\Article as arts;
use think\Db;
use think\Config;
use app\common\model\Artclass as Artclassmdl;

use app\common\model\Articlebanner;


class Articlepro extends HomeBase
{

    protected $art;

    protected $artc;

    protected $bannner;

    protected function _initialize()
    {
        parent::_initialize();
        $this->art = new arts();
        $this->artc = new Artclassmdl();
        $this->bannner=new Articlebanner();
    }

    function gethelplist($classid = '', $page = 1)
    {
        if ($classid) {
            $res = $this->art->where('classid', $classid)
                ->where('isok', 1)
                ->order('updatetime desc')
                ->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);

              $ban=$this->bannner->where("isok",1)->where("classid",$classid)->order('zid desc')->select();


        } else {

            $res = $this->art->where('isok', 1)->order('updatetime desc')->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);
            $ban=$this->bannner->where("isok",1)->order('zid desc')->select();
        }

        $user_id = input('uid');
        $uids = Db::name('user')->where('id', $user_id)->find();

        $result = $this->artc->where('isok', 1)->order('zid desc')->select();

        if($this->request->isAjax()){
            return json([
                'code' => 1002,
                "data" => [
                    'artinfo' => $res,
                    'userinfo' => $uids,
                    'classinfo' => $result,
                    'banner'=>$ban
                ]
            ]);


        }else{

        $this->assign('artinfo', $res);
        $this->assign('userinfo', $uids);
        $this->assign("classinfo", $result);
        $this->assign("banner",$ban);

        return $this->fetch('UsingHelp');

        }
    }

    /**
     * 查询单条记录
     * @param string $id
     * @param string $classid
     * @return mixed
     */

    function gethelpone($id = "", $classid = "")
    {
        $data = input();

        $where = [];

        if ($id)
            $id = $data['id'];

        if ($classid)
            $classid = $data['classid'];

        $result = $this->art->where('id', $id)
            ->where('classid', $classid)
            ->find();

        $this->art->where('id', $id)->setInc('hits', 1);

        $this->assign("helpinfo", $result);

        return $this->fetch('article');
    }

    /**
     * 售后规则
     *
     * @author : slide
     */
    public function customer_service($cid = 0)
    {
        if ($cid) {
            $res = $this->art->where('classid', $cid)->select();
        } else {
            $this->error('没有你要的文章');
        }
        if ($this->request->isAjax()) {
            return json([
                'code' => 2000,
                'data' => $res
            ]);
        } else {
            return $this->fetch('customer_service', [
                'list' => $res
            ]);
        }
    }






}



?>
